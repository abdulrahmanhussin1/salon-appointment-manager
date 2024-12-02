<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Employee;
use App\Traits\AppHelper;
use App\Models\EmployeeWage;
use Illuminate\Http\Request;
use App\Models\EmployeeLevel;
use App\Models\ServiceEmployee;
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
        $services = Service::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();
        return view('admin.pages.employees.employees.create_edit', compact('employeeLevels', 'services', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request)
    {
        try {
            $photo = AppHelper::handleFileUpload($request, 'photo',  "uploads/images/employees", null);
            $idCard = AppHelper::handleFileUpload($request, 'id_card',  "uploads/images/employees/id-cards", null);


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
                'branch_id' => $request->branch_id,
                'created_by' => auth()->user()->id,
            ]);
            $totalSalary = $this->calculateTotalSalary($request);

            EmployeeWage::create([
                'employee_id' => $employee->id,
                'salary_type' => $request->salary_type,
                'basic_salary' => $request->basic_salary ?? 0,
                'bonus_salary' => $request->bonus_salary ?? 0,
                'allowance1' => $request->allowance1 ?? 0,
                'allowance2' => $request->allowance2 ?? 0,
                'allowance3' => $request->allowance3 ?? 0,
                'total_salary' => $request->total_salary == $totalSalary ? $request->total_salary : $totalSalary,
                'working_hours' => $request->working_hours,
                'overtime_rate' => $request->overtime_rate ?? 0,
                'penalty_late_hour' => $request->penalty_late_hour ?? 0,
                'penalty_absence_day' => $request->penalty_absence_day ?? 0,
                'sales_target_settings' => $request->sales_target_settings,
                'start_working_time' => $request->start_working_time,
                'break_time' => $request->break_time,
                'break_duration_minutes' => $request->break_duration_minutes,
            ]);

            if($request->has('service_id'))
            {
                $services = $request->input('service_id');
                foreach ($services as $serviceId) {
                    ServiceEmployee::create([
                        'employee_id' => $employee->id,
                        'service_id' => $serviceId,
                        'commission_type' => $request->input("commission_type.$serviceId"),
                        'commission_value' => $request->input("commission_value.$serviceId"),
                        'is_immediate_commission' => $request->input("is_immediate_commission.$serviceId", false),
                    ]);
                };
            }

            DB::commit();
            Alert::success(__('Success'), __('Created Successfully'));
            return redirect()->back();
        } catch (\Throwable $th) {
            dd($th->getMessage());
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
        $services = Service::where('status', 'active')->select('id', 'name')->get();
        $branches = Branch::where('status', 'active')->select('id', 'name')->get();

        return view('admin.pages.employees.employees.create_edit', compact('employee', 'services', 'employeeLevels', 'employeeWage', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $photo = AppHelper::handleFileUpload($request, 'photo',  "uploads/images/employees", null);
            $idCard = AppHelper::handleFileUpload($request, 'id_card',  "uploads/images/employees/id-cards", null);

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
                'branches_id' => $request->branches_id,
                'updated_by' => auth()->user()->id,
            ]);

            $employeeWage = EmployeeWage::where('employee_id', $employee->id)->first();

            $totalSalary = $this->calculateTotalSalary($request);
            $employeeWage->update([
                'salary_type' => $request->salary_type,
                'basic_salary' => $request->basic_salary ?? 0,
                'bonus_salary' => $request->bonus_salary ?? 0,
                'allowance1' => $request->allowance1 ?? 0,
                'allowance2' => $request->allowance2 ?? 0,
                'allowance3' => $request->allowance3,
                'total_salary' => $request->total_salary == $totalSalary ? $request->total_salary : $totalSalary,
                'working_hours' => $request->working_hours,
                'overtime_rate' => $request->overtime_rate ?? 0,
                'penalty_late_hour' => $request->penalty_late_hour ?? 0,
                'penalty_absence_day' => $request->penalty_absence_day ?? 0,
                'sales_target_settings' => $request->sales_target_settings,
                'start_working_time' => $request->start_working_time,
                'break_time' => $request->break_time,
                'break_duration_minutes' => $request->break_duration_minutes,
            ]);

            ServiceEmployee::where('employee_id', $employee->id)->delete();
            if ($request->has('service_id')) {
                $services = $request->input('service_id');
                foreach ($services as $serviceId) {
                    ServiceEmployee::create([
                        'employee_id' => $employee->id,
                        'service_id' => $serviceId,
                        'commission_type' => $request->input("commission_type.$serviceId"),
                        'commission_value' => $request->input("commission_value.$serviceId"),
                        'is_immediate_commission' => $request->input("is_immediate_commission.$serviceId", false),
                    ]);
                };
            }
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

        EmployeeWage::where('employee_id', $employee->id)->delete();
        ServiceEmployee::where('employee_id', $employee->id)->delete();

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

    public function getRelatedEmployees(Request $request)
    {

        $itemType = $request->query('item_type');
        $itemId = $request->query('item_id');

        if (!$itemType || !$itemId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        if($itemType == 'product')
        {
            $employees = Employee::select('id', 'name')->where('status', 'active')->get();

        }elseif($itemType == 'service'){
            $employeesId = ServiceEmployee::where('service_id',$itemId)->pluck('employee_id')->toArray();
            $employees = Employee::whereIn('id', $employeesId)
            ->select('id', 'name')
                ->get();
        }
        return response()->json($employees);
    }

}
