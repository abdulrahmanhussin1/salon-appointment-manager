<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $statusEnum = $this->status instanceof AppointmentStatus
            ? $this->status
            : ($this->status ? AppointmentStatus::tryFrom($this->status) : AppointmentStatus::REQUESTED);

        $color = $statusEnum?->color() ?? '#ffc107';
        $textColor = in_array($statusEnum, [AppointmentStatus::REQUESTED, AppointmentStatus::RESCHEDULED, AppointmentStatus::EXPIRED], true)
            ? '#212529'
            : '#ffffff';

        return [
            'id' => $this->id,
            'title' => $this->customer->name.' ('.$this->provider->name.')',
            'start' => Carbon::parse($this->start_date)->setTimezone('UTC')->toIso8601String(),
            'end' => Carbon::parse($this->end_date)->setTimezone('UTC')->toIso8601String(),

            'customer_id' => $this->customer_id,
            'provider_id' => $this->provider_id,
            'service_id' => $this->service_id,

            'customer' => $this->customer->name,
            'provider' => $this->provider->name,
            'service' => $this->service->name,

            'start_date' => Carbon::parse($this->start_date)->format('Y-m-d\TH:i'),
            'end_date' => Carbon::parse($this->end_date)->format('Y-m-d\TH:i'),

            'status' => $statusEnum?->value ?? 'requested',
            'status_label' => $statusEnum?->label() ?? ucfirst($this->status ?? 'requested'),
            'status_badge' => $statusEnum?->badgeClass() ?? 'bg-warning text-dark',
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $textColor,

            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,

            'can_checkout' => in_array($statusEnum, [AppointmentStatus::CONFIRMED, AppointmentStatus::CHECKED_IN, AppointmentStatus::IN_SERVICE], true) && ! $this->salesInvoice()->where('status', 'active')->exists(),
            'invoice_id' => $this->salesInvoice?->id,
            'checkout_url' => route('sales_invoices.create', ['appointment_id' => $this->id]),
        ];
    }
}
