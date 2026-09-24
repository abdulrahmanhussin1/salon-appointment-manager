<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'provider_id' => ['required', 'integer', 'exists:employees,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer field is required.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'provider_id.required' => 'The provider field is required.',
            'provider_id.exists' => 'The selected provider is invalid.',
            'service_id.required' => 'The service field is required.',
            'service_id.exists' => 'The selected service is invalid.',
            'start_date.required' => 'The start date and time is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.required' => 'The end date and time is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
        ];
    }
}
