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
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\SalesInvoiceDataTable;

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

        $customers = Customer::select('id', 'name', 'phone', 'dob', 'last_service', 'created_at', 'is_vip','deposit')->where('status', 'active')->get();
        $paymentMethods = PaymentMethod::select('id', 'name')->where('status', 'active')->get();
        $products = Product::select('id', 'name', 'code','price_can_change')
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

        return view('admin.pages.Sales.invoices.create', compact('customers', 'employees', 'products', 'services', 'paymentMethods', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateInvoiceData($request);

        $customer = $this->validateCustomer($validatedData['customer_id']);
        if (!$customer) {
            return back()->withErrors(['customer_id' => 'Selected customer is not active.']);
        }

        $invoiceItems = [];
        $totals = [
            'discount' => 0,
            'tax' => 0,
            'productsTotal' => 0,
            'servicesTotal' => 0,
        ];

        try {
            foreach ($validatedData['items'] as $item) {
                if ($item['type'] === 'product') {
                    $productData = $this->processProduct($item);
                    $invoiceItems[] = $productData['invoiceItem'];
                    $this->updateTotals($totals, $productData);
                } elseif ($item['type'] === 'service') {
                    $serviceData = $this->processService($item);
                    $invoiceItems[] = $serviceData['invoiceItem'];
                    $this->updateTotals($totals, $serviceData);
                }
            }

            $this->createInvoiceTransaction($validatedData, $invoiceItems, $totals);
            Alert::success('success', 'Invoice created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice Creation Error: ' . $e->getMessage());
            Alert::error('error', 'Invoice creation failed');
            return back();
        }
    }

    private function validateInvoiceData(Request $request)
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.type' => 'required|in:product,service',
            'items.*.item_id' => 'required|integer',
            'items.*.code' => 'required|string',
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

    private function validateCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        return $customer && $customer->status === 'active' ? $customer : null;
    }

    private function processProduct($item)
    {
        $product = Product::with('supplierPrices')
        ->where('id', $item['item_id'])
        ->where('status', 'active')
        ->firstOrFail();


        $allocatedPrices = $this->allocateProductPrices($product, $item['quantity']);
        $customerPrice = $allocatedPrices[0]['price'];

        $grossTotal = $this->calculateTotal($allocatedPrices);
        $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
        $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;

        return [
            'grossTotal' => $grossTotal,
            'discount' => $discount,
            'tax' => $tax,
            'invoiceItem' => [
                'product_id' => $product->id,
                'provider_id' =>$item['provider_id'],
                'quantity' => $item['quantity'],
                'customer_price' => $customerPrice,
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'subtotal' => $grossTotal - $discount + $tax,
            ],
        ];
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

    private function createInvoiceTransaction($validatedData, $invoiceItems, $totals)
    {
        DB::beginTransaction();

        $grandTotal = $totals['productsTotal'] + $totals['servicesTotal'];
        $netTotal = $grandTotal - $totals['discount'] + $totals['tax'];
        $deposit = $validatedData['deposit'] ?? 0;

        $invoice = SalesInvoice::create([
            'customer_id' => $validatedData['customer_id'],
            'payment_method_id' => $validatedData['payment_method_id'],
            'payment_method_value' => $validatedData['payment_method_value']?? 0,
            'branch_id' => $validatedData['branch_id'],
            'invoice_date' => $validatedData['invoice_date'],
            'total_amount' => $grandTotal,
            'invoice_discount' => $totals['discount'],
            'invoice_tax' => $totals['tax'],
            'net_total' => $netTotal,
            'invoice_deposit' => $deposit,
            'balance_due' => $netTotal - $deposit - ($validatedData['payment_method_value'] ?? 0) - ($validatedData['cash_payment'] ?? 0),
            'status' => $validatedData['status'],
            'paid_amount_cash' => $validatedData['cash_payment'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        $invoice->salesInvoiceDetails()->createMany($invoiceItems);

        Customer::where('id',$validatedData['custom_id'])->first()->update([
            'deposit' => ($netTotal - $deposit - ($validatedData['payment_method_value'] ?? 0) - ($validatedData['cash_payment'] ?? 0)) ?? 0,
        ]);
        DB::commit();
    }




    /**
     * Display the specified resource.
     */
    public function show(SalesInvoice $salesInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesInvoice $salesInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesInvoice $salesInvoice)
    {
        //
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

    public function showReceipt()
    {
        $items = [
            ['name' => 'Item 1', 'quantity' => 2, 'price' => 50],
            ['name' => 'Item 2', 'quantity' => 1, 'price' => 100],
        ];
        $total = collect($items)->sum(fn($item) => $item['quantity'] * $item['price']);

        return view('admin.pages.Sales.invoices.reciept',   compact('items', 'total'));
    }

}
