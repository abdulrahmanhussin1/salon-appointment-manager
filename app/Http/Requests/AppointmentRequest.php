<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Prepare inputs for validation, auto-computing end_date if omitted.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->end_date) && ! empty($this->start_date) && ! empty($this->service_id)) {
            $service = \App\Models\Service::find($this->service_id);
            if ($service) {
                try {
                    $computedEnd = \Carbon\Carbon::parse($this->start_date)
                        ->addMinutes($service->duration_minutes)
                        ->format('Y-m-d H:i:s');

                    $this->merge([
                        'end_date' => $computedEnd,
                    ]);
                } catch (\Throwable $e) {
                    // Let standard date validation handle parse errors
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('status', 'active')],
            'provider_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('status', 'active')],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('status', 'active')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['nullable', new \Illuminate\Validation\Rules\Enum(\App\Enums\AppointmentStatus::class)],
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

    /**
     * Configure the validator instance to prevent double-booking.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $providerId = $this->input('provider_id');
                $startDate = $this->input('start_date');
                $endDate = $this->input('end_date');

                $appointmentParam = $this->route('appointment') ?? $this->route('id');
                $excludeId = is_object($appointmentParam) ? $appointmentParam->id : (is_numeric($appointmentParam) ? (int) $appointmentParam : null);

                if ($providerId && $startDate && $endDate) {
                    $conflict = \App\Models\Appointment::findConflict(
                        (int) $providerId,
                        $startDate,
                        $endDate,
                        $excludeId
                    );

                    if ($conflict) {
                        $validator->errors()->add(
                            'provider_id',
                            __('The selected provider already has an appointment scheduled during this time slot.')
                        );
                    }
                }
            }
        });
    }
}
