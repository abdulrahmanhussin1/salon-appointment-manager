<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:255',
            'salutation' => 'nullable|in:Mr,Mrs,Ms,Dr,Eng',
            'status' => 'nullable|in:active,inactive',
            'gender' => 'nullable|in:male,female',
            'deposit' => 'nullable|numeric',
            'added_from' => 'required|in:online,referral,walk_in,advertisement,direct',
            'dob' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_vip' => 'nullable|boolean',
            'address' => 'nullable|string|max:255',
        ];

        $rules['email'] = 'nullable|email|unique:customers,email'; // Email must be unique when creating a new user
        $rules['phone'] = 'nullable|unique:customers,phone'; // Phone must be unique when creating a new user
        if ($this->routeIs('customers.update')) {
            $rules['email'] = [
                'nullable',
                'email',
                Rule::unique('customers', 'email')->ignore($this->route('customer')) // Ignore the current user's email
            ];
            $rules['phone'] = [
                'nullable',
                Rule::unique('customers', 'phone')->ignore($this->route('customer')) // Ignore the current user's phone number
            ];
        }

        return $rules;
    }
}
