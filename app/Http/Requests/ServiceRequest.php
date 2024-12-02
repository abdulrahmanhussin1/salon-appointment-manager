<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $isUpdate
                ? Rule::unique('services', 'name')->ignore($this->service)
                : 'unique:services,name',
            ],
            'notes' => 'nullable|string',
            'duration' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_target' => 'required|boolean',
            'outside_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'branch_id' => 'required|integer|exists:branches,id',

            'is_immediate_commission' => 'required|array',
            'commission_type' => 'required|array',

            'is_immediate_commission.*' => 'required|boolean',
            'commission_type.*' => 'required|string|in:percentage,value',

            'tool_id' => 'nullable|array',
            'tool_id.*' => 'nullable|exists:tools,id',

            'employee_id' => 'nullable|array',
            'employee_id.*' => 'nullable|exists:employees,id',

            'commission_value.*' => 'nullable|numeric|min:0',

            'product_id' => 'nullable|array',
            'product_id.*' => 'nullable|exists:products,id',
        ];

    }
}
