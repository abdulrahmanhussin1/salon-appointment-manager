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
        $customers = Customer::select('id', 'name', 'dob', 'last_service', 'created_at', 'is_vip')->where('status', 'active')->get();
        $paymentMethods = PaymentMethod::select('id', 'name')->where('status', 'active')->get();
        $products = Product::select('id', 'name', 'code')
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

        $services = Service::select('id', 'name', 'price')->where('status', 'active')->get();
        $employees = Employee::select('id', 'name')->where('status', 'active')->get();
        $branches = Branch::select('id', 'name')->where('status', 'active')->get();
        return view('admin.pages.Sales.invoices.create', compact('customers', 'employees', 'products', 'services', 'paymentMethods', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.type' => 'required|in:product,service',
            'items.*.item_id' => 'required|integer',
            'items.*.code' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.provider' => 'nullable|integer|exists:employees,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.tax' => 'nullable|numeric|min:0|max:100',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'deposit' => 'nullable|numeric|min:0',
            'invoice_date'=> 'required|date' ,
            'branch_id' => 'required|exists:branches,id',
            'status'=>'required|string|in:active,inactive,draft',
        ]);
        // Fetch customer and validate status
        $customer = Customer::findOrFail($validatedData['customer_id']);
        if ($customer->status !== 'active') {
            Alert::error('error', 'Selected customer is not active.');
            return back();
        }

        $invoiceItems = [];
        $totalDiscount = 0;
        $totalTax = 0;
        $servicesTotal = 0;
        $productsTotal = 0;

        try {
            foreach ($validatedData['items'] as $item) {
                if ($item['type'] === 'product') {
                    // Fetch product with prices
                    $product = Product::with('supplierPrices')
                        ->where('id', $item['item_id'])
                        ->where('status', 'active')
                        ->first();

                    if (!$product) {
                        return back()->withErrors(['items' => 'Invalid or inactive product selected.']);
                    }



                    // Allocate prices dynamically
                    $allocatedPrices = [];
                    $requestedQuantity = $item['quantity'];
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

                    $totalPrice = collect($allocatedPrices)->reduce(function ($sum, $price) {
                        return $sum + ($price['price'] * $price['quantity']);
                    }, 0);

                    $grossTotal = $totalPrice;
                    $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
                    $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;
                    $due = $grossTotal - $discount + $tax;

                    $productsTotal += $grossTotal;
                    $totalDiscount += $discount;
                    $totalTax += $tax;

                    $invoiceItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'customer_price' => $grossTotal,
                        'discount' => $discount,
                        'tax' => $tax,
                        'subtotal' => $due,
                    ];
                } elseif ($item['type'] === 'service') {
                    // Fetch service
                    $service = Service::where('id', $item['item_id'])
                        ->where('status', 'active')
                        ->first();

                    if (!$service) {
                        return back()->withErrors(['items' => 'Invalid or inactive service selected.']);
                    }

                    $grossTotal = $service->price * $item['quantity'];
                    $discount = ($grossTotal * ($item['discount'] ?? 0)) / 100;
                    $tax = (($grossTotal - $discount) * ($item['tax'] ?? 0)) / 100;
                    $due = $grossTotal - $discount + $tax;

                    $servicesTotal += $grossTotal;
                    $totalDiscount += $discount;
                    $totalTax += $tax;

                    $invoiceItems[] = [
                        'service_id' => $service->id,
                        'quantity' => $item['quantity'],
                        'customer_price' => $grossTotal,
                        'discount' => $discount,
                        'tax' => $tax,
                        'subtotal' => $due,
                    ];
                }
            }

            // Calculate totals
            $grandTotal = $servicesTotal + $productsTotal - $totalDiscount + $totalTax;
            $deposit = $validatedData['deposit'] ?? 0;
            $netTotal = $grandTotal - $deposit;

            DB::beginTransaction();
            // Create invoice
            $invoice = SalesInvoice::create([
                'customer_id' => $validatedData['customer_id'],
                'payment_method_id' => $validatedData['payment_method_id'],
                'branch_id' => $validatedData['branch_id'],
                'invoice_date' => $validatedData['invoice_date'],
                'total_amount' => $grandTotal,
                'invoice_discount' => $totalDiscount,
                'invoice_tax' => $totalTax,
                'invoice_deposit' => $deposit,
                'balance_due' => $netTotal - $deposit,
                'status' => $validatedData['status'],
                'created_by' => auth()->id(),
            ]);

            // Save invoice items
            foreach ($invoiceItems as $item) {
                $invoice->salesInvoiceDetails()->create($item);
            }
            DB::commit();
            Alert::success('success','invoice created successfully');
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            Alert::error('error','invoice failed to create');
            return redirect()->back();
        }
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
}
