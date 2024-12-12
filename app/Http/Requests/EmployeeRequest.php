<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Modify this if you have specific authorization logic
    }

    public function rules()
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => 'required|string|max:100',
            'email' => [
                'nullable',
                'email',
                $isUpdate
                ? Rule::unique('employees', 'email')->ignore($this->employee)
                : 'unique:employees,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                $isUpdate
                ? Rule::unique('employees', 'phone')->ignore($this->employee)
                : 'unique:employees,phone',
            ],
            'national_id' => [
                'nullable',
                'string',
                'max:20',
                $isUpdate
                ? Rule::unique('employees', 'national_id')->ignore($this->employee)
                : 'unique:employees,national_id',
            ],
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:2048',
            'id_card' => 'nullable|image|max:2048',
            'hiring_date' => 'nullable|date|before_or_equal:termination_date|after:dob',
            'dob' => 'nullable|date|before:today|before:termination_date|before:hiring_date',
            'finger_print_code' => [
                'nullable',
                'string',
                'max:50',
                $isUpdate
                ? Rule::unique('employees', 'finger_print_code')->ignore($this->employee)
                : 'unique:employees,finger_print_code',
            ],
            'job_title' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'status' => 'required|in:active,inactive',
            'employee_level_id' => 'required|exists:employee_levels,id',
            'inactive_reason' => 'nullable|string|max:255',
            'termination_date' => [
                'nullable',
                'date',
                'after_or_equal:hiring_date|after:dob',
            ],
            'salary_type' => 'nullable|in:daily,weekly,monthly,commission',
            'basic_salary' => 'nullable|numeric|min:0',
            'bonus_salary' => 'nullable|numeric|min:0',
            'allowance1' => 'nullable|numeric|min:0',
            'allowance2' => 'nullable|numeric|min:0',
            'allowance3' => 'nullable|numeric|min:0',
            'total_salary' => 'nullable|numeric|min:0',
            'working_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'penalty_late_hour' => 'nullable|numeric|min:0',
            'penalty_absence_day' => 'nullable|numeric|min:0',
            'sales_target_settings' => 'nullable|in:no,total_sales,employee_daily_service',
            'start_working_time' => 'nullable|date_format:H:i|before:break_time',
            'break_time' => 'nullable|date_format:H:i|after:start_working_time',
            'branch_id' => 'required|integer|exists:branches,id',

            'service_id'=>'nullable|array',
            'service_id.*'=>'nullable|integer|exists:services,id',

            'is_immediate_commission' => 'nullable|array',
            'commission_type' => 'nullable|array',

            'is_immediate_commission.*' => 'required|boolean',
            'commission_type.*' => 'required|string|in:percentage,value',

            'commission_value.*' => 'nullable|numeric|min:0',

        ];
    }


    public function messages()
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'The phone number is required.',
            'phone.unique' => 'The phone number must be unique.',
            'national_id.unique' => 'The national ID must be unique.',
            'photo.image' => 'The file must be an image.',
            'photo.max' => 'Photo size must not exceed 2 MB.',
            'hiring_date.required' => 'Hiring date is required.',
            'dob.before' => 'Date of birth must be before today.',
            'status.in' => 'Status must be either active or inactive.',
            'employee_level_id.required' => 'Employee level is required.',
            'termination_date.before' => 'Termination date must be before today.',
            'termination_date.before_or_equal' => 'Termination date must be on or before the hiring date.',
            'salary_type.in' => 'Salary type must be either daily, weekly, monthly, or commission.',
            'basic_salary.numeric' => 'Basic salary must be a number.',
            'bonus_salary.numeric' => 'Bonus salary must be a number.',
            'allowance1.numeric' => 'Allowance 1 must be a number.',
            'allowance2.numeric' => 'Allowance 2 must be a number.',
            'allowance3.numeric' => 'Allowance 3 must be a number.',
            'total_salary.numeric' => 'Total sales must be a number.',
            'working_hours.numeric' => 'Working hours must be a number.',
            'overtime_rate.numeric' => 'Overtime rate must be a number.',
            'penalty_late_hour.numeric' => 'Penalty for late hour must be a number.',
            'penalty_absence_day.numeric' => 'Penalty for absence day must be a number.',
            'sales_target_settings.in' => 'Sales target settings must be either no, total_sales, or employee_daily_service.',
            'start_working_time.date_format' => 'Start working time must be in the format H:i.',
            'break_time.date_format' => 'Break time must be in the format H:i.',
        ];
    }

}
