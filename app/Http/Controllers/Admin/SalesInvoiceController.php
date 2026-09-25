<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SalesInvoiceDataTable;
use App\Http\Controllers\Controller;
use App\Models\AdminPanelSetting;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

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
                    ->first(fn ($price) => $price->quantity > 0);

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
        try {
            return DB::transaction(function () use ($validatedData) {
                $customer = Customer::where('id', $validatedData['customer_id'])
                    ->lockForUpdate()  // This is crucial for preventing race conditions
                    ->first();

                if (! $customer || $customer->status !== 'active') {
                    throw new \Exception('Selected customer is not active.');
                }

                $invoiceItems = [];
                $totals = [
                    'discount' => 0,
                    'tax' => 0,
                    'productsTotal' => 0,
                    'servicesTotal' => 0,
                ];

                $warnings = [];

                // Process invoice items
                foreach ($validatedData['items'] as $item) {
                    if ($item['type'] === 'product') {
                        // Lock the product inventory
                        $productData = DB::transaction(function () use ($item, $validatedData) {
                            return $this->processProduct($item, $validatedData['status'], $validatedData['branch_id']);
                        });
                        $invoiceItems[] = $productData['invoiceItem'];
                        $this->updateTotals($totals, $productData);
                    } elseif ($item['type'] === 'service') {
                        $serviceData = $this->processService($item, $validatedData['status'], $validatedData['branch_id'], $warnings);
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
                        'balance_due' => $invoice->balance_due - $depositUsage['used_amount'],
                    ]);
                }

                // Update customer's last_service on active invoice creation ONLY if invoice contains services (BR-P009)
                $hasServices = collect($validatedData['items'])->contains(fn ($it) => ($it['type'] ?? '') === 'service');
                if ($validatedData['status'] === 'active' && $hasServices) {
                    if (empty($customer->last_service) || $validatedData['invoice_date'] >= $customer->last_service) {
                        $customer->update([
                            'last_service' => $validatedData['invoice_date'],
                        ]);
                    }
                }

                $response = [
                    'invoice_id' => $invoice->id,
                ];
                if (! empty($warnings)) {
                    $response['warnings'] = $warnings;
                }

                return response()->json($response, 200);
            }, 5); // 5 retries for deadlock cases
        } catch (\Throwable $th) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $th->getMessage()], 422);
            }

            Alert::error(__('Error'), $th->getMessage());

            return redirect()->back()->withInput();
        }
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
            if ($remainingToUse <= 0) {
                break;
            }

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
                'notes' => 'Deposit usage for invoice #'.$invoiceId,
                'status' => 'used',
                'used_in_transaction_id' => $deposit->id,
                'created_by' => auth()->id(),
            ]);

            $usedAmount += $useAmount;
            $remainingToUse -= $useAmount;
        }

        return [
            'used_amount' => $usedAmount,
            'remaining_requested' => $remainingToUse,
        ];
    }

    private function processProduct($item, $status = 'active', $branchId = null)
    {
        return DB::transaction(function () use ($item, $status, $branchId) {
            $product = Product::with('supplierPrices')
                ->where('id', $item['item_id'])
                ->where('status', 'active')
                ->lockForUpdate()  // Add lock for product
                ->firstOrFail();

            // Check if there's enough inventory in the branch
            $availableQuantity = $this->checkInventoryAvailability($product->id, $item['quantity'], $branchId);
            if (! $availableQuantity) {
                throw new \Exception("Insufficient inventory for product {$product->name}");
            }

            $allocatedPrices = $this->allocateProductPrices($product, $item['quantity']);
            $customerPrice = $allocatedPrices[0]['price'];

            $grossTotal = $this->calculateTotal($allocatedPrices);
            $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
            $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;

            // Deduct from inventory ONLY if active
            if ($status === 'active') {
                $this->deductFromInventory($product->id, $item['quantity'], $branchId);
            }

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
                    'commission_type' => null,
                    'commission_rate' => 0.00,
                    'commission_amount' => 0.00,
                    'is_immediate_commission' => false,
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

    private function processService($item, $status = 'active', $branchId = null, &$warnings = [])
    {
        $service = Service::where('id', $item['item_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // If price_can_change is true and a valid custom price is provided, use it; otherwise use the service base price
        $unitPrice = ($service->price_can_change && isset($item['price']) && is_numeric($item['price']))
            ? (float) $item['price']
            : (float) $service->price;

        $grossTotal = $unitPrice * $item['quantity'];
        $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
        $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;

        // Query commission configuration from service_employees pivot table (BR-P010)
        $serviceEmployee = DB::table('service_employees')
            ->where('service_id', $service->id)
            ->where('employee_id', $item['provider_id'])
            ->first();

        $commissionType = null;
        $commissionRate = 0.00;
        $commissionAmount = 0.00;
        $isImmediateCommission = false;

        if ($serviceEmployee) {
            $commissionType = $serviceEmployee->commission_type;
            $commissionRate = (float) $serviceEmployee->commission_value;
            $isImmediateCommission = (bool) $serviceEmployee->is_immediate_commission;

            if ($commissionRate > 0) {
                if ($commissionType === 'percentage') {
                    // BR-P010: commission = (subtotal - discount) * (commission_value / 100)
                    $netBeforeTax = max(0, $grossTotal - $discount);
                    $commissionAmount = round($netBeforeTax * ($commissionRate / 100), 2);
                } elseif ($commissionType === 'value') {
                    // BR-P010: commission = commission_value * quantity
                    $commissionAmount = round($commissionRate * (float) $item['quantity'], 2);
                }
            }
        }

        // Deduct service consumables ONLY if status is active (REQ-014)
        if ($status === 'active') {
            $this->deductServiceConsumables($service->id, $item['quantity'], $branchId, $warnings);
        }

        return [
            'grossTotal' => $grossTotal,
            'discount' => $discount,
            'tax' => $tax,
            'invoiceItem' => [
                'service_id' => $service->id,
                'provider_id' => $item['provider_id'],
                'quantity' => $item['quantity'],
                'customer_price' => $unitPrice,
                'discount' => $discount,
                'tax' => $tax,
                'subtotal' => $grossTotal - $discount + $tax,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'is_immediate_commission' => $isImmediateCommission,
            ],
        ];
    }

    private function deductServiceConsumables($serviceId, $serviceQuantity, $branchId = null, &$warnings = [])
    {
        $service = Service::with('products')->find($serviceId);
        if (! $service) {
            return;
        }

        foreach ($service->products as $product) {
            $qtyPerService = $product->pivot->product_quantity ?? 1;
            $totalQuantity = (int) $qtyPerService * (int) $serviceQuantity;

            if ($totalQuantity > 0) {
                $this->deductConsumableFromInventory($product->id, $totalQuantity, $branchId, $warnings);
            }
        }
    }

    private function deductConsumableFromInventory($productId, $quantity, $branchId = null, &$warnings = [])
    {
        $product = Product::find($productId);
        $productName = $product?->name ?? "Product #{$productId}";

        $blockInsufficient = (bool) AdminPanelSetting::value('block_insufficient_consumables');

        $availableQuery = InventoryProduct::where('product_id', $productId);
        if ($branchId) {
            $availableQuery->whereHas('inventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }
        $availableStock = (int) $availableQuery->sum('quantity');

        if ($availableStock < $quantity) {
            if ($blockInsufficient) {
                throw new \Exception("Insufficient inventory for consumable {$productName}");
            }
            $warnings[] = "Insufficient stock for consumable {$productName} (required: {$quantity}, available: {$availableStock}).";
        }

        // FIFO deduction from branch inventory
        $query = InventoryProduct::where('product_id', $productId);
        if ($branchId) {
            $query->whereHas('inventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $inventoryProducts = $query->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingQuantity = $quantity;
        $sourceInventoryId = null;

        // Deduct from records with positive stock first
        foreach ($inventoryProducts as $inventoryProduct) {
            if ($remainingQuantity <= 0) {
                break;
            }

            if ($inventoryProduct->quantity > 0) {
                $deduct = min($remainingQuantity, $inventoryProduct->quantity);
                $inventoryProduct->quantity -= $deduct;
                $inventoryProduct->save();

                $remainingQuantity -= $deduct;
                if (! $sourceInventoryId) {
                    $sourceInventoryId = $inventoryProduct->inventory_id;
                }
            }
        }

        // If remaining quantity remains (insufficient stock permitted by setting),
        // deduct remaining into negative stock on the last record, or find/create branch inventory product
        if ($remainingQuantity > 0) {
            if ($inventoryProducts->isNotEmpty()) {
                $target = $inventoryProducts->last();
                $target->quantity -= $remainingQuantity;
                $target->save();
                $sourceInventoryId = $sourceInventoryId ?? $target->inventory_id;
            } else {
                $inventory = $branchId ? Inventory::where('branch_id', $branchId)->first() : Inventory::first();
                if ($inventory) {
                    InventoryProduct::create([
                        'inventory_id' => $inventory->id,
                        'product_id' => $productId,
                        'quantity' => -$remainingQuantity,
                    ]);
                    $sourceInventoryId = $inventory->id;
                }
            }
        }

        if (! $sourceInventoryId && $branchId) {
            $inventory = Inventory::where('branch_id', $branchId)->first();
            $sourceInventoryId = $inventory?->id;
        }

        // Create inventory transaction record of type service_consumption
        $inventoryTransaction = InventoryTransaction::create([
            'transaction_type' => 'service_consumption',
            'source_inventory_id' => $sourceInventoryId,
            'total_before_discount' => 0,
            'net_total' => 0,
        ]);

        InventoryTransactionDetail::create([
            'inventory_transaction_id' => $inventoryTransaction->id,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        return $inventoryTransaction;
    }

    private function allocateProductPrices($product, $requestedQuantity)
    {
        $allocatedPrices = [];
        foreach ($product->supplierPrices->sortBy('created_at') as $price) {
            if ($requestedQuantity <= 0) {
                break;
            }
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
            fn ($sum, $price) => $sum + ($price['price'] * $price['quantity']),
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

    private function checkInventoryAvailability($productId, $requestedQuantity, $branchId = null)
    {
        $query = InventoryProduct::where('product_id', $productId);

        if ($branchId) {
            $query->whereHas('inventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $totalAvailable = $query->sum('quantity');

        return $totalAvailable >= $requestedQuantity;
    }

    private function deductFromInventory($productId, $quantity, $branchId = null)
    {
        $query = InventoryProduct::where('product_id', $productId)
            ->where('quantity', '>', 0);

        if ($branchId) {
            $query->whereHas('inventory', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        // Get all inventory records for this product, ordered by oldest first with pessimistic locking
        $inventoryProducts = $query->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingQuantity = $quantity;
        $transactions = [];

        foreach ($inventoryProducts as $inventoryProduct) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $deductQuantity = min($remainingQuantity, $inventoryProduct->quantity);

            // Update inventory quantity
            $inventoryProduct->quantity -= $deductQuantity;
            $inventoryProduct->save();

            // Create inventory transaction
            $transactions[] = [
                'inventory_id' => $inventoryProduct->inventory_id,
                'product_id' => $productId,
                'quantity' => $deductQuantity,
            ];

            $remainingQuantity -= $deductQuantity;
        }

        if ($remainingQuantity > 0) {
            $product = Product::find($productId);
            throw new \Exception('Insufficient inventory to deduct for product '.($product?->name ?? $productId));
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
                'quantity' => $quantity,
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
     * Activate a draft invoice and deduct inventory.
     */
    public function activate(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Only draft invoices can be activated.'], 422);
            }
            Alert::error(__('Error'), __('Only draft invoices can be activated.'));

            return redirect()->back();
        }

        try {
            $warnings = [];

            DB::transaction(function () use ($salesInvoice, &$warnings) {
                $productDetails = $salesInvoice->salesInvoiceDetails()
                    ->whereNotNull('product_id')
                    ->get();

                // Aggregate product quantities to ensure multiple line items for same product are properly checked
                $requestedQuantities = [];
                foreach ($productDetails as $detail) {
                    $requestedQuantities[$detail->product_id] = ($requestedQuantities[$detail->product_id] ?? 0) + $detail->quantity;
                }

                // 1. Verify sufficient inventory for all product line items in the invoice's branch
                foreach ($requestedQuantities as $productId => $totalQty) {
                    if (! $this->checkInventoryAvailability($productId, $totalQty, $salesInvoice->branch_id)) {
                        $product = Product::find($productId);
                        throw new \Exception('Insufficient inventory for product '.($product?->name ?? $productId));
                    }
                }

                // 2. Deduct inventory for all product line items in the invoice's branch
                foreach ($productDetails as $detail) {
                    $this->deductFromInventory($detail->product_id, $detail->quantity, $salesInvoice->branch_id);
                }

                // 3. Deduct service consumables for all service line items in the invoice's branch (REQ-014)
                $serviceDetails = $salesInvoice->salesInvoiceDetails()
                    ->whereNotNull('service_id')
                    ->get();

                foreach ($serviceDetails as $detail) {
                    $this->deductServiceConsumables($detail->service_id, $detail->quantity, $salesInvoice->branch_id, $warnings);
                }

                // 4. Mark invoice as active
                $salesInvoice->update(['status' => 'active']);

                // 5. Update customer last_service only if invoice contains services (BR-P009)
                $hasServices = $salesInvoice->salesInvoiceDetails()->whereNotNull('service_id')->exists();
                if ($hasServices) {
                    $customer = Customer::where('id', $salesInvoice->customer_id)
                        ->lockForUpdate()
                        ->first();

                    if ($customer && (empty($customer->last_service) || $salesInvoice->invoice_date >= $customer->last_service)) {
                        $customer->update(['last_service' => $salesInvoice->invoice_date]);
                    }
                }
            });

            if (request()->wantsJson()) {
                $response = [
                    'message' => 'Invoice activated successfully.',
                    'invoice_id' => $salesInvoice->id,
                ];
                if (! empty($warnings)) {
                    $response['warnings'] = $warnings;
                }

                return response()->json($response, 200);
            }

            if (! empty($warnings)) {
                Alert::warning(__('Warning'), implode("\n", $warnings));
            } else {
                Alert::success(__('Success'), __('Invoice activated successfully.'));
            }

            return redirect()->back();
        } catch (\Throwable $th) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $th->getMessage()], 422);
            }

            Alert::error(__('Error'), $th->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Void the specified sales invoice.
     */
    public function void(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $salesInvoice->void($validated['reason'], auth()->id());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Invoice voided successfully.',
                    'invoice_id' => $salesInvoice->id,
                    'status' => $salesInvoice->status,
                    'voided_at' => $salesInvoice->voided_at?->toIso8601String(),
                    'voided_by' => $salesInvoice->voided_by,
                    'void_reason' => $salesInvoice->void_reason,
                ], 200);
            }

            Alert::success(__('Success'), __('Invoice voided successfully.'));

            return redirect()->back();
        } catch (\Throwable $th) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $th->getMessage()], 422);
            }

            Alert::error(__('Error'), $th->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        if ($request->input('status') === 'active' && $salesInvoice->status === 'draft') {
            return $this->activate($salesInvoice);
        }

        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            abort(403, 'Only draft invoices can be deleted.');
        }

        DB::transaction(function () use ($salesInvoice) {
            // Restore any customer deposits consumed by this draft invoice (REV-002)
            $salesInvoice->reverseCustomerDepositUsage();

            $salesInvoice->salesInvoiceDetails()->delete();
            $salesInvoice->delete();
        });

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Draft invoice deleted successfully.'], 200);
        }

        Alert::success(__('Success'), __('Draft invoice deleted successfully.'));

        return redirect()->back();
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

        return view('admin.pages.Sales.invoices.reciept', compact('invoice'));
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
                'price_can_change' => $item->price_can_change,
            ];
        } else {
            $service = Service::findOrFail($id);

            return [
                'price' => $service->price,
                'price_can_change' => $service->price_can_change,
            ];
        }
    }
}
