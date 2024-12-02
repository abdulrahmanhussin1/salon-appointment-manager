<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Update this based on your authorization logic.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'expense_type_id'   => 'required|exists:expense_types,id',
            'description'       => 'nullable|string|max:1000',
            'amount'            => 'required|numeric|min:0',
            'paid_at'           => 'required|date',
            'invoice_number' => 'nullable|string|max:20',
            'paid_amount'       => 'required|numeric|min:0',
            'balance'           => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'status'            => 'required|in:active,inactive',
            'branch_id'         => 'required|exists:branches,id',
            'created_by'        => 'required|exists:users,id',
        ];

        // For updates, allow `updated_by` to be nullable and optional.
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['updated_by'] = 'nullable|exists:users,id';
        }

        return $rules;
    }
}
