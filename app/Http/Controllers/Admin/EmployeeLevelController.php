<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\EmployeeLevel;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\DataTables\EmployeeLevelDataTable;
use App\Http\Requests\EmployeeLevelRequest;

class EmployeeLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(EmployeeLevelDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.employees.employee_levels.index');
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
    public function store(EmployeeLevelRequest $request)
    {

        EmployeeLevel::create([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'created_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeLevel $employeeLevel)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeLevel $employeeLevel)
    {
        return view('admin.pages.employees.employee_levels.edit', compact('employeeLevel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeLevelRequest $request, EmployeeLevel $employeeLevel)
    {
        $employeeLevel->update([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'updated_by'=>auth()->id()
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('employee_levels.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeLevel $employeeLevel)
    {
        $employeeLevel->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
