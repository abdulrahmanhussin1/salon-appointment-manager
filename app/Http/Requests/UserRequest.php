<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
    public function rules(): array
    {
       if ($this->method() == 'PUT') {

        return [
            'name'=>'required|string|max:100',
            'email'=>'required|unique:users,email,'.$this->user->id,
            'password'=> 'nullable|string|min:8|confirmed',
            'photo'=> 'nullable|image|mimes:jpeg,png,jpg,gif',
            'status' => 'required|string|in:active,inactive',
            'role_id'=> 'required|integer|exists:roles,id',
            'employee_id'=> 'nullable|integer|exists:employees,id|unique:users,employee_id,'.$this->user->id,
        ];
    }

    return [
            'name'=>'required|string|max:100',
            'email'=>'required|unique:users,email',
            'password'=> 'required|string|min:8|confirmed |max:255',
            'photo'=> 'nullable|image|mimes:jpeg,png,jpg,gif',
            'status' => 'required|string|in:active,inactive',
            'role_id'=> 'required|integer|exists:roles,id',
            'employee_id'=> 'nullable|integer|exists:employees,id|unique:users,employee_id',
        ];

    }
}
