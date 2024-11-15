<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:suppliers,name,'.$this->supplier->id,
            'email' => 'nullable|email|unique:suppliers,email,'.$this->supplier->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'initial_balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
            ];
        }
        return [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'email' => 'nullable|email|unique:suppliers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'initial_balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ];
    }
}
