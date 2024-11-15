<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Models\SupplierPrice;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\DataTables\PurchaseInvoiceDataTable;
use App\Http\Requests\PurchaseInvoiceRequest;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PurchaseInvoiceDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.suppliers.purchase_invoices.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.suppliers.purchase_invoices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseInvoiceRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create the purchase invoice
            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'total_amount' => $request->total_amount,
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'created_by' => auth()->id(),
            ]);

            // Save purchase invoice details
            $purchaseInvoice->saveDetails($request->details);

            // Create supplier transaction
            SupplierTransaction::create([
                'supplier_id' => $request->supplier_id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'transaction_type' => SupplierTransaction::TYPE_PURCHASE,
                'amount' => $request->total_amount,
                'notes' => 'Purchase invoice #' . $request->invoice_number,
            ]);

            DB::commit();

            return response()->json(['message' => 'Purchase invoice created successfully'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating purchase invoice: ' . $e->getMessage());

            return back()->with('error', 'Failed to create purchase invoice. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
