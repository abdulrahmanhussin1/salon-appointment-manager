<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\DataTables\BranchDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use RealRashid\SweetAlert\Facades\Alert;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BranchDataTable $dataTable)
    {
        $managers = Employee::select('id','name')->where('status','active')->get();
        $users = User::select('id', 'name')->where('status', 'active')->get();
        return $dataTable->render('admin.pages.settings.branches.index',compact('managers','users'));
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
    public function store(BranchRequest $request)
    {
        $branch = Branch::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'manager_id' => $request->manager_id,
            'created_by' => auth()->id(),
        ]);

        Inventory::create([
            'name'=> $branch->name .' Inventory',
            'branch_id' => $branch->id,
            'created_by' => auth()->id(),
        ]);
        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        $managers = Employee::select('id','name')->where('status','active')->get();

        return view('admin.pages.settings.branches.edit', compact('branch','managers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch)
    {

        $branch->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'manager_id' => $request->manager_id,
            'updated_by' => auth()->id(),
        ]);

        $inventory = $branch->inventory;
        if(!empty($inventory))
        {
            $inventory->update([
                'name' => $branch->name . ' Inventory',
                'branch_id' => $branch->id,
                'updated_by' => auth()->id(),
            ]);
        }else{
            Inventory::create([
                'name' => $branch->name . ' Inventory',
                'branch_id' => $branch->id,
                'created_by' => auth()->id(),
            ]);
        }


        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('branches.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        try{
            $branch->delete();
            Alert::success(__('Success'), __('Deleted Successfully'));
        }
        catch(\Exception $e){
            Alert::error(__('Error'), __('Failed to delete branch. check this branch not related with any data '));
        }

    }
}
