<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        if ($this->method() == 'PUT') {
            return [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:employees,email,'.$this->employee->id,
                'phone' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:500',
                'image' => 'nullable|image|max:2048',
                'hiring_date' => 'nullable|date',
                'dob' => 'nullable|date|before:today',
                'finger_print_code' => 'nullable|string|max:50|unique:employees,finger_print_code,'.$this->employee->id,
                'job_title' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female',
                'status' => 'required|in:active,inactive',
                'employee_level_id' => 'nullable|exists:employee_levels,id',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:2048',
            'hiring_date' => 'nullable|date',
            'dob' => 'nullable|date|before:today',
            'finger_print_code' => 'nullable|string|max:50|unique:employees,finger_print_code',
            'job_title' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'status' => 'required|in:active,inactive',
            'employee_level_id' => 'nullable|exists:employee_levels,id',
        ];
    }

}
