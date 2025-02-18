<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierTransaction;
use App\Http\Controllers\Controller;
use App\DataTables\SupplierDataTable;
use App\Http\Requests\SupplierRequest;
use RealRashid\SweetAlert\Facades\Alert;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SupplierDataTable $dataTable)
    {
        return $dataTable->render("admin.pages.suppliers.index");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request)
    {
        try {

            DB::beginTransaction();
            $supplier = Supplier::create([
                "name" => $request->name,
                "email" => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'initial_balance' => $request->initial_balance ?? 0,
                'status' => $request->status,
                'created_by' => auth()->id(),
            ]);

            SupplierTransaction::create([
                'supplier_id' => $supplier->id,
                'reference_type' => 'initial_balance',
                'reference_id' => 0, // Set a meaningful reference ID if applicable
                'amount' => $supplier->initial_balance,
                'notes' => 'Initial Balance'
            ]);

            DB::commit();

            Alert::success(__('Success'), __('Created Successfully'));
            return redirect()->back();
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error('Failure in creating supplier');
            return redirect()->back();

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('admin.pages.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        try {
            DB::beginTransaction();

            $supplier->update([
                "name" => $request->name,
                "email" => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'initial_balance' => $request->initial_balance ?? 0,
                'status' => $request->status,
                'updated_by' => auth()->id(),
            ]);

            $initialBalance = SupplierTransaction::where('supplier_id', $supplier->id)->where('type', 'initial_balance')->first();
            $initialBalance->update([
                'amount' => $supplier->initial_balance,
            ]);

            DB::commit();
            Alert::success(__('Success'), __('Updated Successfully'));
            return redirect()->route('suppliers.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error('Failure in Updating supplier');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
