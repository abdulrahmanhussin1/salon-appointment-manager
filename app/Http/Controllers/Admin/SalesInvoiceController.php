<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\SupplierPrice;
use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\InventoryProduct;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Query\Builder;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\SalesInvoiceDataTable;
use App\Models\InventoryTransactionDetail;

class SalesInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SalesInvoiceDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.Sales.invoices.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $customers = Customer::select('id', 'name', 'phone', 'dob', 'last_service', 'created_at', 'is_vip')
            ->selectSub(function (Builder $query) {
                $query->from('customer_transactions')
                    ->whereColumn('customer_transactions.customer_id', 'customers.id')
                    ->where('status', 'available') // Only get available deposits
                    ->where('amount', '>', 0)
                    ->select(DB::raw('SUM(amount)')) // Sum all available deposits
                    ->limit(1);
            }, 'deposit')
            ->where('status', 'active')
            ->get();


        $paymentMethods = PaymentMethod::select('id', 'name')->where('status', 'active')->where('name', '!=', 'cash')->get();
        $products = Product::select('id', 'name', 'code', 'price_can_change')
            ->with(['supplierPrices:id,product_id,quantity,customer_price,created_at'])
            ->where('status', 'active')
            ->get()
            ->map(function ($product) {
                // Find the first price where quantity > 0
                $firstPriceWithQuantity = $product->supplierPrices
                    ->sortBy('created_at') // Sort by created_at
                    ->first(fn($price) => $price->quantity > 0);

                // Set the price to the first valid customer price
                $product->price = $firstPriceWithQuantity ? $firstPriceWithQuantity->customer_price : null;

                // Optionally, unset supplierPrices if not needed
                unset($product->supplierPrices);

                return $product;
            });

        $services = Service::select('id', 'name', 'price', 'price_can_change')->where('status', 'active')->get();
        $employees = Employee::select('id', 'name')->where('status', 'active')->get();
        $branches = Auth::user()->hasRole('cashier')
            ? Branch::where('id', Auth::user()->employee?->branch_id)->get(['id', 'name'])
            : Branch::where('status', 'active')->get(['id', 'name']);
        $categories = ProductCategory::where('status', 'active')->select('id', 'name')->get();
        $serviceCategories = ServiceCategory::where('status', 'active')->select('id', 'name')->get();


        return view(
            'admin.pages.Sales.invoices.create',
            compact('customers', 'employees', 'products', 'services', 'paymentMethods', 'branches', 'categories', 'serviceCategories')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateInvoiceData($request);

        // Get an exclusive lock on the customer to prevent concurrent transactions
        return DB::transaction(function () use ($validatedData, $request) {
            $customer = Customer::where('id', $validatedData['customer_id'])
                ->lockForUpdate()  // This is crucial for preventing race conditions
                ->first();

            if (!$customer || $customer->status !== 'active') {
                throw new \Exception('Selected customer is not active.');
            }

            $invoiceItems = [];
            $totals = [
                'discount' => 0,
                'tax' => 0,
                'productsTotal' => 0,
                'servicesTotal' => 0,
            ];

            // Process invoice items
            foreach ($validatedData['items'] as $item) {
                if ($item['type'] === 'product') {
                    // Lock the product inventory
                    $productData = DB::transaction(function () use ($item) {
                        return $this->processProduct($item);
                    });
                    $invoiceItems[] = $productData['invoiceItem'];
                    $this->updateTotals($totals, $productData);
                } elseif ($item['type'] === 'service') {
                    $serviceData = $this->processService($item);
                    $invoiceItems[] = $serviceData['invoiceItem'];
                    $this->updateTotals($totals, $serviceData);
                }
            }

            // Create invoice and process deposit in the same transaction
            $invoice = $this->createInvoiceTransaction($validatedData, $invoiceItems, $totals);

            if ($validatedData['deposit'] > 0) {
                $depositUsage = $this->processDepositUsage(
                    $validatedData['customer_id'],
                    $validatedData['deposit'],
                    $invoice->id
                );

                // Update invoice with used deposit
                $invoice->update([
                    'invoice_deposit' => $depositUsage['used_amount'],
                    'balance_due' => $invoice->balance_due - $depositUsage['used_amount']
                ]);
            }

            return response()->json([
                'invoice_id' => $invoice->id,
            ], 200);
        }, 5); // 5 retries for deadlock cases
    }

    private function processDepositUsage($customerId, $requestedDepositAmount, $invoiceId)
    {
        // Get available deposits with a lock
        $availableDeposits = CustomerTransaction::where('customer_id', $customerId)
            ->where('status', 'available')
            ->where('amount', '>', 0)
            ->lockForUpdate()  // Add lock here
            ->orderBy('created_at')
            ->get();

        $remainingToUse = $requestedDepositAmount;
        $usedAmount = 0;

        foreach ($availableDeposits as $deposit) {
            if ($remainingToUse <= 0) break;

            // Create a snapshot of the current deposit amount
            $currentDepositAmount = $deposit->amount;

            if ($currentDepositAmount <= $remainingToUse) {
                // Use entire deposit
                $useAmount = $currentDepositAmount;
                $deposit->status = 'used';
            } else {
                // Partially use deposit
                $useAmount = $remainingToUse;
                $deposit->amount -= $useAmount;
            }

            $deposit->save();

            // Create transaction record for deposit usage
            CustomerTransaction::create([
                'customer_id' => $customerId,
                'reference_type' => 'invoice',
                'reference_id' => $invoiceId,
                'amount' => -$useAmount,
                'notes' => 'Deposit usage for invoice #' . $invoiceId,
                'status' => 'used',
                'used_in_transaction_id' => $deposit->id,
                'created_by' => auth()->id()
            ]);

            $usedAmount += $useAmount;
            $remainingToUse -= $useAmount;
        }

        return [
            'used_amount' => $usedAmount,
            'remaining_requested' => $remainingToUse
        ];
    }

    private function processProduct($item)
    {
        return DB::transaction(function () use ($item) {
            $product = Product::with('supplierPrices')
                ->where('id', $item['item_id'])
                ->where('status', 'active')
                ->lockForUpdate()  // Add lock for product
                ->firstOrFail();

            // Check if there's enough inventory
            $availableQuantity = $this->checkInventoryAvailability($product->id, $item['quantity']);
            if (!$availableQuantity) {
                throw new \Exception("Insufficient inventory for product {$product->name}");
            }

            $allocatedPrices = $this->allocateProductPrices($product, $item['quantity']);
            $customerPrice = $allocatedPrices[0]['price'];

            $grossTotal = $this->calculateTotal($allocatedPrices);
            $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
            $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;

            // Deduct from inventory
            $this->deductFromInventory($product->id, $item['quantity']);

            return [
                'grossTotal' => $grossTotal,
                'discount' => $discount,
                'tax' => $tax,
                'invoiceItem' => [
                    'product_id' => $product->id,
                    'provider_id' => $item['provider_id'],
                    'quantity' => $item['quantity'],
                    'customer_price' => $customerPrice,
                    'discount' => $item['discount'],
                    'tax' => $item['tax'],
                    'subtotal' => $grossTotal - $discount + $tax,
                ],
            ];
        });
    }

    private function createInvoiceTransaction($validatedData, $invoiceItems, $totals)
    {
        $grandTotal = $totals['productsTotal'] + $totals['servicesTotal'];
        $netTotal = $grandTotal - $totals['discount'] + $totals['tax'];

        $invoice = SalesInvoice::create([
            'customer_id' => $validatedData['customer_id'],
            'payment_method_id' => $validatedData['payment_method_id'],
            'payment_method_value' => $validatedData['payment_method_value'] ?? 0,
            'branch_id' => $validatedData['branch_id'],
            'invoice_date' => $validatedData['invoice_date'],
            'total_amount' => $grandTotal,
            'invoice_discount' => $totals['discount'],
            'invoice_tax' => $totals['tax'],
            'net_total' => $netTotal,
            'invoice_deposit' => 0, // Will be updated after deposit processing
            'balance_due' => $netTotal - ($validatedData['payment_method_value'] ?? 0) - ($validatedData['cash_payment'] ?? 0),
            'status' => $validatedData['status'],
            'paid_amount_cash' => $validatedData['cash_payment'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        $invoice->salesInvoiceDetails()->createMany($invoiceItems);

        return $invoice;
    }
    private function validateInvoiceData(Request $request)
    {

        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.type' => 'required|in:product,service',
            'items.*.item_id' => 'required|integer',
            'items.*.code' => 'required',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.provider_id' => 'required|integer|exists:employees,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.tax' => 'nullable|numeric|min:0|max:100',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'deposit' => 'nullable|numeric|min:0',
            'invoice_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|string|in:active,inactive,draft',
            'cash_payment' => 'nullable|numeric|min:0',
            'payment_method_value' => 'nullable|numeric|min:0',
        ]);
    }

    private function processService($item)
    {
        $service = Service::where('id', $item['item_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $grossTotal = $service->price * $item['quantity'];
        $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
        $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;

        return [
            'grossTotal' => $grossTotal,
            'discount' => $discount,
            'tax' => $tax,
            'invoiceItem' => [
                'service_id' => $service->id,
                'provider_id' => $item['provider_id'],
                'quantity' => $item['quantity'],
                'customer_price' => $service->price,
                'discount' => $discount,
                'tax' => $tax,
                'subtotal' => $grossTotal - $discount + $tax,
            ],
        ];
    }

    private function allocateProductPrices($product, $requestedQuantity)
    {
        $allocatedPrices = [];
        foreach ($product->supplierPrices->sortBy('created_at') as $price) {
            if ($requestedQuantity <= 0) break;
            $allocatedQuantity = min($requestedQuantity, $price->quantity);
            $allocatedPrices[] = [
                'price' => $price->customer_price,
                'quantity' => $allocatedQuantity,
            ];
            $requestedQuantity -= $allocatedQuantity;
        }
        return $allocatedPrices;
    }

    private function calculateTotal($allocatedPrices)
    {
        return collect($allocatedPrices)->reduce(
            fn($sum, $price) => $sum + ($price['price'] * $price['quantity']),
            0
        );
    }

    private function updateTotals(&$totals, $itemData)
    {
        $totals['discount'] += $itemData['discount'];
        $totals['tax'] += $itemData['tax'];
        if (isset($itemData['invoiceItem']['product_id'])) {
            $totals['productsTotal'] += $itemData['grossTotal'];
        } else {
            $totals['servicesTotal'] += $itemData['grossTotal'];
        }
    }
    private function checkInventoryAvailability($productId, $requestedQuantity)
    {
        $totalAvailable = InventoryProduct::where('product_id', $productId)
            ->sum('quantity');

        return $totalAvailable >= $requestedQuantity;
    }

    private function deductFromInventory($productId, $quantity)
    {
        // Get all inventory records for this product, ordered by oldest first
        $inventoryProducts = InventoryProduct::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingQuantity = $quantity;
        $transactions = [];

        foreach ($inventoryProducts as $inventoryProduct) {
            if ($remainingQuantity <= 0) break;

            $deductQuantity = min($remainingQuantity, $inventoryProduct->quantity);

            // Update inventory quantity
            $inventoryProduct->quantity -= $deductQuantity;
            $inventoryProduct->save();

            // Create inventory transaction
            $transactions[] = [
                'inventory_id' => $inventoryProduct->inventory_id,
                'product_id' => $productId,
                'quantity' => $deductQuantity
            ];

            $remainingQuantity -= $deductQuantity;
        }

        // Create inventory transaction record
        $transaction = DB::transaction(function () use ($transactions, $productId, $quantity) {
            $inventoryTransaction = InventoryTransaction::create([
                'transaction_type' => 'sales',
                'source_inventory_id' => $transactions[0]['inventory_id'], // Using first inventory as source
                'total_before_discount' => 0, // Set appropriate values based on your needs
                'net_total' => 0, // Set appropriate values based on your needs
            ]);

            // Create transaction details
            InventoryTransactionDetail::create([
                'inventory_transaction_id' => $inventoryTransaction->id,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);

            return $inventoryTransaction;
        });

        return $transaction;
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesInvoice $salesInvoice)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesInvoice $salesInvoice)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesInvoice $salesInvoice)
    {
        abort(404);
    }
    public function getItem(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'product') {
            return Product::select('id', 'name', 'code')->where('status', 'active')->get();
        } elseif ($type === 'service') {
            return Service::select('id', 'name')->where('status', 'active')->get();
        }

        return response()->json([]);
    }

    public function showReceipt($id)
    {
        $invoice = SalesInvoice::findOrFail($id);
        return view('admin.pages.Sales.invoices.reciept',   compact('invoice'));
    }

    public function bookAppointment()
    {
        return view('admin.pages.Sales.booking.index');
    }

    public function getByType(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'product') {
            return ProductCategory::where('status', 'active')->get(['id', 'name']);
        } else {
            return ServiceCategory::where('status', 'active')->get(['id', 'name']);
        }
    }

    // ItemController.php
    public function getByCategory(Request $request)
    {
        $type = $request->input('type');
        $categoryId = $request->input('category_id');

        if ($type === 'product') {
            return Product::where('category_id', $categoryId)
                ->where('status', 'active')
                ->get(['id', 'name', 'code']);
        } else {
            return Service::where('service_category_id', $categoryId)
                ->where('status', 'active')
                ->get(['id', 'name']);
        }
    }

    public function getDetails(Request $request, $id)
    {
        $type = $request->input('type');

        if ($type === 'product') {
            $item = Product::with(['supplierPrices' => function ($query) {
                $query->where('quantity', '>', 0)
                    ->orderBy('created_at', 'desc');
            }])->findOrFail($id);

            return [
                'price' => $item->supplierPrices->first()?->customer_price ?? 0,
                'price_can_change' => $item->price_can_change
            ];
        } else {
            $service = Service::findOrFail($id);
            return [
                'price' => $service->price,
                'price_can_change' => $service->price_can_change
            ];
        }
    }
}
