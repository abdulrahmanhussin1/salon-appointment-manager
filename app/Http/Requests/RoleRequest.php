<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
                'name'=>'required|unique:roles,name,'.$this->role->id,
                'description'=>'nullable|string|max:500',
                'status'=> 'required|string|in:active,inactive',
            ];
        }
        return [
            'name'=>'required|unique:roles,name',
            'description'=>'nullable|string|max:500',
            'status'=> 'required|string|in:active,inactive',
        ];
    }
}
