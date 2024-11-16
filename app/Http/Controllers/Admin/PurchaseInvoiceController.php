<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
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
        $suppliers = Supplier::where('status', 'active')->select('id', 'name')->get();
        $products = Product::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();
        $latestInvoiceNumber = null;

        if (!empty(auth()->user()->employee_id)) {
            $employee = auth()->user()->employee;

            if ($employee && $employee->branch) {
                $latestInvoiceNumber = $employee->branch->purchaseInvoices->max('invoice_number');
            } else {
                Log::warning('Employee or branch is missing for user ID: ' . auth()->id());
            }
        }
        return view('admin.pages.suppliers.purchase_invoices.create', compact('products', 'branches', 'suppliers', 'latestInvoiceNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseInvoiceRequest $request)
    {

        try {
            DB::beginTransaction();
            $invoiceDiscount = $request->invoice_discount ?? 0;
            $details = request()->details;

            foreach ($details as &$detail) {
                if (empty($detail['discount'])) {
                    $detail['discount'] = 0;
                }
            }

            request()->merge(['details' => $details]);
            // Create the purchase invoice
            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'total_amount' => $request->total_amount,
                'invoice_discount' => $invoiceDiscount,
                'invoice_notes' => $request->invoice_notes,
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'created_by' => auth()->id(),
            ]);

            // Save purchase invoice details
            $purchaseInvoice->saveDetails($details);

            // Create supplier transaction
            SupplierTransaction::create([
                'supplier_id' => $request->supplier_id,
                'reference_id' => $purchaseInvoice->id,
                'reference_type' => SupplierTransaction::TYPE_PURCHASE,
                'amount' => $request->total_amount,
                'notes' => 'Purchase invoice #' . $purchaseInvoice->invoice_number,
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
    public function show(PurchaseInvoice $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $invoice = PurchaseInvoice::with(['details'])->findOrFail($id);

        $suppliers = Supplier::where('status','active')->select('id', 'name')->get();
        $products = Product::where('status','active')->select('id', 'name')->get();
        $branches = Branch::where('status','active')->select('id', 'name')->get();

        $latestInvoiceNumber = null;

        $user = auth()->user();
        if (!empty($user->employee_id) && $user->employee && $user->employee->branch) {
            $latestInvoiceNumber = $user->employee->branch->purchaseInvoices()->max('invoice_number');
        } else {
            Log::warning('Employee or branch is missing for user ID: ' . $user->id);
        }

        return view('admin.pages.suppliers.purchase_invoices.edit', [
            'invoice' => $invoice,
            'suppliers' => $suppliers,
            'products' => $products,
            'branches' => $branches,
            'latestInvoiceNumber' => $latestInvoiceNumber,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseInvoice $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $id)
    {
        abort(404);
    }
}
