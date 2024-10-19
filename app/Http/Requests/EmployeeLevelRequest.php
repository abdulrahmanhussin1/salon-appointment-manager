<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeLevelRequest extends FormRequest
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
                'name'=>'required|string|unique:employee_levels,name,'.$this->employee_level->id,
                'description'=>'nullable|string|max:500',
                'status'=> 'required|string|in:active,inactive',
            ];
        }
        return [
            'name'=>'required|string|unique:employee_levels,name',
            'description'=>'nullable|string|max:500',
            'status'=> 'required|string|in:active,inactive',
        ];
    }
}
