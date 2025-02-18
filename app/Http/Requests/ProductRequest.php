<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        // Base validation rules for all requests
        $rules = [
            'name' => 'required|string|max:255|unique:products,name',
            'code' => 'required|integer|unique:products,code',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit_id' => 'nullable|exists:units,id',
            // 'supplier_price' => 'required|numeric|min:0',
            // 'customer_price' => 'required|numeric|min:0',
            // 'outside_price' => 'nullable|numeric|min:0',
            'initial_quantity' => 'nullable|integer|min:0',
            'is_target' => 'required|boolean',
            'price_can_change' => 'required|boolean',

            'type' => 'required|in:operation,sales',
            'status' => 'required|in:active,inactive',
            'branch_id' => 'required|integer|exists:branches,id',

        ];

        // If the request is a PUT (update), adjust the 'unique' validation rules
        if ($this->method() == 'PUT') {
            $rules['name'] = 'required|string|max:255|unique:products,name,' . $this->product->id;
            $rules['code'] = 'required|integer|unique:products,code,' . $this->product->id;
        }

        return $rules;
    }
}
