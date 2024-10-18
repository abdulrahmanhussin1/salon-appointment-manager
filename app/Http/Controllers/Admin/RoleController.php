<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use Illuminate\Http\Request;
use App\DataTables\RolesDataTable;
use App\Http\Requests\RoleRequest;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RolesDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.roles.create_edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'status'=> $request->status,
            'created_by'=>auth()->id()
        ]);
        $role->givePermissionTo($request->permissions);
        Alert::success(__('Success'),__('Create Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        return view('admin.pages.roles.create_edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'status'=> $request->status,
        ]);
        $role->syncPermissions($request->permissions);
        Alert::success(__('Success'), __('Update Successfully'));
        return redirect()->route('roles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        Alert::success(__('Success'), __('Delete Successfully'));
    }
}
