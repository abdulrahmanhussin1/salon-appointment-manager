<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    // Authorize the request
    public function authorize()
    {
        return true; // Change to your authorization logic if needed
    }

    // Define the validation rules
    public function rules()
    {
        $branchId = $this->route('branch') ? $this->route('branch')->id : null;

        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20|unique:branches,phone,' . $branchId,
            'email' => 'nullable|email|max:255|unique:branches,email,' . $branchId,
            'status' => 'required|in:active,inactive',
            'manger_id'=>'nullable|integer|exists:employees,id'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The branch name is required.',
            'name.string' => 'The branch name must be a string.',
            'name.max' => 'The branch name must not exceed 255 characters.',
            'address.string' => 'The address must be a string.',
            'phone.string' => 'The phone number must be a string.',
            'phone.max' => 'The phone number must not exceed 20 characters.',
            'phone.unique' => 'The phone number has already been taken.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email must not exceed 255 characters.',
            'email.unique' => 'The email has already been taken.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
