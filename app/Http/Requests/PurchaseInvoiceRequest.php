<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseInvoiceRequest extends FormRequest
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
        return [
            'invoice_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[a-zA-Z0-9-]+$/',
                $this->routeIs('purchase_invoices.update')
                    ? Rule::unique('purchase_invoices', 'invoice_number')->ignore($this->route('purchase_invoice'))
                    : Rule::unique('purchase_invoices', 'invoice_number'),
            ],

            'invoice_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0.01',
            'invoice_discount'=>'nullable|numeric|min:0',
            'invoice_notes'=>'nullable|string|max:500',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:active,inactive',
            'branch_id' => 'nullable|exists:branches,id',

            'details' => 'required|array',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.supplier_price' => 'required|numeric|min:0',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.subtotal' => 'required|numeric|min:0',
            'details.*.discount' => 'nullable|numeric|min:0',
            'details.*.notes' => 'nullable|string|max:500',
        ];
    }
}
