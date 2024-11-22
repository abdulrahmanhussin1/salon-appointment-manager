<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                $this->routeIs('inventories.update') ?
                Rule::unique('inventories', 'name')->ignore($this->route('inventory'))
                : Rule::unique('inventories', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
