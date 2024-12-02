<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\SupplierPrice;
use App\Http\Controllers\Controller;

class SalesInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::select('id','name')->where('status','active')->get();

        $products = Product::select('id', 'name', 'code')
        ->with(['supplierPrices:id,product_id,customer_price,created_at'])
        ->where('status', 'active')
        ->get()
        ->map(function ($product) {
            $product->supplierPrices = $product->supplierPrices->sortBy('created_at'); // Sort by created_at to get the oldest prices first

            $allocatedPrices = [];
            $requestedQuantity = 100; // Example quantity needed, adjust as needed

            foreach ($product->supplierPrices as $price) {
                // if ($requestedQuantity <= 0) {
                //     break; // Stop if the requested quantity has been met
                // }

                //$allocatedQuantity = min($requestedQuantity, $price->quantity);
                // $allocatedPrices[] = [
                //     'price' => $price->customer_price,
                //     'quantity' => $allocatedQuantity,
                // ];

                // $requestedQuantity -= $allocatedQuantity; // Reduce the quantity needed
            }

            $product->price = $allocatedPrices; // Store the allocated prices in the product
            //unset($product->supplierPrices); // Remove the supplierPrices data if not needed

            return $product;
        });

        dd($products);


        $services = Service::select('id','name','price')->where('status','active')->get();
        $employees = Employee::select('id','name')->where('status','active')->get();
        return view('admin.pages.Sales.invoices.create',compact('customers','employees','products','services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function getItem(Request $request) {
        $type = $request->input('type');

        if ($type === 'product') {
            return Product::select('id', 'name', 'code')->where('status', 'active')->get();
        } elseif ($type === 'service') {
            return Service::select('id', 'name')->where('status', 'active')->get();
        }

        return response()->json([]);
    }


}
