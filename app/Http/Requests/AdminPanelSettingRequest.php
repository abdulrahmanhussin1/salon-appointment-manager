<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminPanelSettingRequest extends FormRequest
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
            'system_name' => 'required|string|max:255',
            'system_phone' => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:20',
            'system_notes' => 'nullable|string|max:1000',
            'system_address' => 'nullable|string|max:255',
            'system_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
