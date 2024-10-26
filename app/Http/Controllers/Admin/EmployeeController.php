<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Traits\AppHelper;
use App\Models\EmployeeWage;
use Illuminate\Http\Request;
use App\Models\EmployeeLevel;
use Illuminate\Support\Facades\DB;
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
        $employeeLevels = EmployeeLevel::where('status', 'active')->select('id', 'name')->get();
        return view('admin.pages.employees.employees.create_edit', compact('employeeLevels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request)
    {
        try {
            $photo = AppHelper::handleFileUpload($request, 'photo', null, "uploads/images/employees");
            $idCard = AppHelper::handleFileUpload($request, 'id_card', null, "uploads/images/employees/id-cards");


            DB::beginTransaction();
            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'national_id' => $request->national_id,
                'address' => $request->address,
                'notes' => $request->notes,
                'photo' => $photo,
                'id_card' => $idCard,
                'hiring_date' => $request->hiring_date,
                'dob' => $request->dob,
                'finger_print_code' => $request->finger_print_code,
                'job_title' => $request->job_title,
                'gender' => $request->gender,
                'status' => $request->status,
                'employee_level_id' => $request->employee_level_id,
                'inactive_reason' => $request->inactive_reason,
                'termination_date' => $request->termination_date,
                'created_by' => auth()->user()->id,
            ]);
            $totalSalary = $this->calculateTotalSalary($request);

            EmployeeWage::create([
                'employee_id' => $employee->id,
                'salary_type' => $request->salary_type,
                'basic_salary' => $request->basic_salary,
                'bonus_salary' => $request->bonus_salary,
                'allowance1' => $request->allowance1,
                'allowance2' => $request->allowance2,
                'allowance3' => $request->allowance3,
                'total_salary' => $request->total_salary == $totalSalary ? $request->total_salary : $totalSalary,
                'working_hours' => $request->working_hours,
                'overtime_rate' => $request->overtime_rate,
                'penalty_late_hour' => $request->penalty_late_hour,
                'penalty_absence_day' => $request->penalty_absence_day,
                'sales_target_settings' => $request->sales_target_settings,
                'start_working_time' => $request->start_working_time,
                'break_time' => $request->break_time,
                'break_duration' => $request->break_duration,
            ]);
            DB::commit();
            Alert::success(__('Success'), __('Created Successfully'));
            return redirect()->back();
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error(__('error'), __('error in create employee , please try again'));
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $employeeLevels = EmployeeLevel::where('status', 'active')->select('id', 'name')->get();
        $employeeWage = EmployeeWage::where('employee_id', $employee->id)->first();
        return view('admin.pages.employees.employees.create_edit', compact('employee', 'employeeLevels','employeeWage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $photo = AppHelper::handleFileUpload($request, 'photo', null, "uploads/images/employees");
            $idCard = AppHelper::handleFileUpload($request, 'id_card', null, "uploads/images/employees/id-cards");

            DB::beginTransaction();
            $employee->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'national_id' => $request->national_id,
                'address' => $request->address,
                'notes' => $request->notes,
                'photo' => $photo,
                'id_card' => $idCard,
                'hiring_date' => $request->hiring_date,
                'dob' => $request->dob,
                'finger_print_code' => $request->finger_print_code,
                'job_title' => $request->job_title,
                'gender' => $request->gender,
                'status' => $request->status,
                'employee_level_id' => $request->employee_level_id,
                'inactive_reason' => $request->inactive_reason,
                'termination_date' => $request->termination_date,
                'updated_by' => auth()->user()->id,
            ]);

            $employeeWage = EmployeeWage::where('employee_id', $employee->id)->first();

            $totalSalary = $this->calculateTotalSalary($request);
            $employeeWage->update([
                'salary_type' => $request->salary_type,
                'basic_salary' => $request->basic_salary,
                'bonus_salary' => $request->bonus_salary,
                'allowance1' => $request->allowance1,
                'allowance2' => $request->allowance2,
                'allowance3' => $request->allowance3,
                'total_salary' => $request->total_salary == $totalSalary ? $request->total_salary : $totalSalary,
                'working_hours' => $request->working_hours,
                'overtime_rate' => $request->overtime_rate,
                'penalty_late_hour' => $request->penalty_late_hour,
                'penalty_absence_day' => $request->penalty_absence_day,
                'sales_target_settings' => $request->sales_target_settings,
                'start_working_time' => $request->start_working_time,
                'break_time' => $request->break_time,
                'break_duration' => $request->break_duration,
            ]);
            DB::commit();
            Alert::success(__('Success'), __('Updated Successfully'));
            return redirect()->route('employees.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::error(__('error'), __('error in update employee , please try again'));
            return redirect()->back();
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        if ($employee->photo && Storage::exists($employee->photo)) {
            Storage::delete($employee->photo);
        }
        if ($employee->id_card && Storage::exists($employee->id_card)) {
            Storage::delete($employee->id_card);
        }
        $employeeWage = EmployeeWage::where('employee_id', $employee->id)->first();
        $employeeWage->delete();
        $employee->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }


    private function calculateTotalSalary($request)
    {
        return ($request->basic_salary ?? 0) +
               ($request->bonus_salary ?? 0) +
               ($request->allowance1 ?? 0) +
               ($request->allowance2 ?? 0) +
               ($request->allowance3 ?? 0);
    }
}
