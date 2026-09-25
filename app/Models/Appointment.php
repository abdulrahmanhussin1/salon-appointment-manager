<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Appointment extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'appointments';

    protected $casts = [
        'status' => AppointmentStatus::class,
        'cancelled_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Employee::class, 'provider_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Transition the appointment to a new lifecycle state.
     *
     * @throws ValidationException
     */
    public function transitionTo(AppointmentStatus|string $targetStatus, ?string $cancellationReason = null): self
    {
        $targetEnum = is_string($targetStatus) ? AppointmentStatus::tryFrom($targetStatus) : $targetStatus;

        if (! $targetEnum) {
            throw ValidationException::withMessages([
                'status' => __('Invalid appointment status.'),
            ]);
        }

        $currentStatus = $this->status instanceof AppointmentStatus
            ? $this->status
            : AppointmentStatus::from($this->status ?? AppointmentStatus::REQUESTED->value);

        if (! $currentStatus->canTransitionTo($targetEnum)) {
            throw ValidationException::withMessages([
                'status' => __("Cannot transition appointment from ':from' to ':to'.", [
                    'from' => $currentStatus->value,
                    'to' => $targetEnum->value,
                ]),
            ]);
        }

        if ($targetEnum === AppointmentStatus::CANCELLED) {
            if (empty(trim((string) $cancellationReason))) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => __('A cancellation reason is required to cancel an appointment.'),
                ]);
            }
            $this->cancelled_at = now();
            $this->cancellation_reason = trim($cancellationReason);
        }

        $this->status = $targetEnum;
        $this->updated_by = auth()->id();
        $this->save();

        return $this;
    }

    /**
     * Find an active conflicting appointment for the provider in the given time slot.
     */
    public static function findConflict(int $providerId, \Carbon\Carbon|string $startDate, \Carbon\Carbon|string $endDate, ?int $excludeAppointmentId = null): ?self
    {
        $startStr = \Carbon\Carbon::parse($startDate)->format('Y-m-d H:i:s');
        $endStr = \Carbon\Carbon::parse($endDate)->format('Y-m-d H:i:s');

        return static::where('provider_id', $providerId)
            ->when($excludeAppointmentId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->active()
            ->where('start_date', '<', $endStr)
            ->where('end_date', '>', $startStr)
            ->first();
    }

    /**
     * Scope for active scheduling appointments that block provider availability.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::REQUESTED->value,
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::CHECKED_IN->value,
            AppointmentStatus::IN_SERVICE->value,
        ]);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus(Builder $query, AppointmentStatus|string $status): Builder
    {
        $val = $status instanceof AppointmentStatus ? $status->value : $status;

        return $query->where('status', $val);
    }

    public function isRequested(): bool
    {
        return $this->status === AppointmentStatus::REQUESTED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === AppointmentStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::CANCELLED;
    }

    public function isCheckedIn(): bool
    {
        return $this->status === AppointmentStatus::CHECKED_IN;
    }

    public function isInService(): bool
    {
        return $this->status === AppointmentStatus::IN_SERVICE;
    }

    public function isCompleted(): bool
    {
        return $this->status === AppointmentStatus::COMPLETED;
    }

    public function isNoShow(): bool
    {
        return $this->status === AppointmentStatus::NO_SHOW;
    }
}
