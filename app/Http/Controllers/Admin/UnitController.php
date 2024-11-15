<?php

namespace App\Http\Controllers\Admin;

use App\Models\Unit;
use Illuminate\Http\Request;
use App\DataTables\UnitDataTable;
use App\Http\Requests\UnitRequest;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use RealRashid\SweetAlert\Facades\Alert;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UnitDataTable $dataTable)
    {
        $branches = Branch::where('status', 'active')->select('id','name')->get();
        return $dataTable->render('admin.pages.settings.units.index',compact('branches'));
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
    public function store(UnitRequest $request)
    {
        Unit::create([
            'name'=> $request->name,
            'description'=> $request->description,
            'symbol'=> $request->symbol,
            'status'=> $request->status,
            'branch_id'=>$request->branch_id,
            'created_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        abort(404);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        $branches = Branch::where('status','active')->select('id','name')->get();
        return view('admin.pages.settings.units.edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitRequest $request, Unit $unit)
    {
        $unit->update([
            'name'=> $request->name,
            'description'=> $request->description,
            'symbol'=> $request->symbol,
            'status'=> $request->status,
            'branch_id'=>$request->branch_id,
            'updated_by'=>auth()->id()
            ]);
            Alert::success(__('Success'), __('Updated Successfully'));
            return redirect()->route('tools.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
