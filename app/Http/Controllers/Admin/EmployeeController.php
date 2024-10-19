<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeLevel;
use App\Http\Controllers\Controller;
use App\DataTables\EmployeeDataTable;
use App\Http\Requests\EmployeeRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(EmployeeDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.employees.employees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employeeLevels = EmployeeLevel::where('status', 'active')->select('id','name')->get();
        return view('admin.pages.employees.employees.create_edit',compact('employeeLevels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request)
    {
        $image = NULL;
        if ($request->hasFile('image')) {
            $image = Storage::putFileAs("uploads/images/employees", $request->image,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_photo.'. $request->image->getClientOriginalExtension());
        }

        $employee = Employee::create([
            'name'=> $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'address'=> $request->address,
            'notes'=> $request->notes,
            'image'=> $image,
            'hiring_date'=> $request->hiring_date,
            'dob'=> $request->dob,
            'finger_print_code'=> $request->finger_print_code,
            'job_title'=> $request->job_title,
            'gender'=> $request->gender,
            'status'=> $request->status,
            'employee_level_id'=> $request->employee_level_id,
            'created_by'=> auth()->user()->id,
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        abort(4044);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $employeeLevels = EmployeeLevel::where('status', 'active')->select('id','name')->get();
        return view('admin.pages.employees.employees.create_edit', compact('employee', 'employeeLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $image = $employee->image;
        if ($request->hasFile('image')) {
            if ($employee->image && Storage::exists($employee->image))  // Delete old image if exists
            {
                Storage::delete($employee->image);
            }
            $image = Storage::putFileAs("uploads/images/employees", $request->image,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_photo.'. $request->image->getClientOriginalExtension());
        }

        $employee->update([
            'name'=> $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'address'=> $request->address,
            'notes'=> $request->notes,
            'image'=> $image,
            'hiring_date'=> $request->hiring_date,
            'dob'=> $request->dob,
            'finger_print_code'=> $request->finger_print_code,
            'job_title'=> $request->job_title,
            'gender'=> $request->gender,
            'status'=> $request->status,
            'employee_level_id'=> $request->employee_level_id,
            'updated_by'=> auth()->user()->id,
        ]);

        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('employees.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        if ($employee->image && Storage::exists($employee->image)) {
            Storage::delete($employee->image);
        }
        $employee->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
