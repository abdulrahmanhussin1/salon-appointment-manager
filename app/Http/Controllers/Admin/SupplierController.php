<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
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
        return $dataTable->render("admin.pages.products.suppliers.index");
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

        Supplier::create([
            "name"=> $request->name,
            "email"=> $request->email,
            'phone'=> $request->phone,
            'address'=> $request->address,
            'status'=> $request->status,
            'created_by'=>auth()->id(),
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
        } catch (\Throwable $th) {
            dd($th->getMessage());
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
        return view('admin.pages.products.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplier->update([
            "name"=> $request->name,
            "email"=> $request->email,
            'phone'=> $request->phone,
            'address'=> $request->address,
            'status'=> $request->status,
            'updated_by'=>auth()->id(),
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('suppliers.index');
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
