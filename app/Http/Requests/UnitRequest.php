<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
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
                'name'=>'required|string|unique:units,name,'.$this->unit->id,
                'description'=>'nullable|string|max:500',
                'symbol'=>'nullable|string|max:10',
                'status'=> 'required|string|in:active,inactive',
                'branch_id' => 'required|integer|exists:branches,id',

            ];
        }
        return [
            'name'=>'required|string|unique:units,name',
            'symbol'=>'nullable|string|max:10',
            'description'=>'nullable|string|max:500',
            'status'=> 'required|string|in:active,inactive',
            'branch_id' => 'required|integer|exists:branches,id',

        ];
    }
}
