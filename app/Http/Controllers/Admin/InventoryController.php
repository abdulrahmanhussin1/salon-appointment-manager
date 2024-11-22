<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\InventoryDataTable;
use App\Http\Requests\InventoryRequest;
use RealRashid\SweetAlert\Facades\Alert;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InventoryDataTable $dataTable)
    {
        $branches = Branch::select('id', 'name')->where('status', 'active')->get();

        return $dataTable->render('admin.pages.inventories.index',compact('branches'));
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
    public function store(InventoryRequest $request)
    {
        Inventory::create([
            'name' => $request->name,
            'branch_id' => $request->branch_id,
            'description' => $request->description,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory)
    {
        $branches = Branch::select('id', 'name')->where('status','active')->get();
        return view('admin.pages.inventories.edit',compact('inventory','branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryRequest $request, Inventory $inventory)
    {
        $inventory->update([
            'name' => $request->name,
            'branch_id' => $request->branch_id,
            'description' => $request->description,
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('inventories.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));

    }
}
