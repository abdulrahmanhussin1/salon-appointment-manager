<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case REQUESTED = 'requested';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case RESCHEDULED = 'rescheduled';
    case CHECKED_IN = 'checked_in';
    case IN_SERVICE = 'in_service';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => __('Requested'),
            self::CONFIRMED => __('Confirmed'),
            self::REJECTED => __('Rejected'),
            self::CANCELLED => __('Cancelled'),
            self::RESCHEDULED => __('Rescheduled'),
            self::CHECKED_IN => __('Checked In'),
            self::IN_SERVICE => __('In Service'),
            self::COMPLETED => __('Completed'),
            self::NO_SHOW => __('No Show'),
            self::EXPIRED => __('Expired'),
        };
    }

    /**
     * Get hex color code for calendar and UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::REQUESTED => '#ffc107',   // Warning / Amber
            self::CONFIRMED => '#0d6efd',   // Primary / Blue
            self::REJECTED => '#dc3545',    // Danger / Red
            self::CANCELLED => '#6c757d',   // Secondary / Gray
            self::RESCHEDULED => '#0dcaf0', // Info / Cyan
            self::CHECKED_IN => '#6f42c1',  // Indigo / Purple
            self::IN_SERVICE => '#fd7e14',  // Orange
            self::COMPLETED => '#198754',   // Success / Green
            self::NO_SHOW => '#212529',     // Dark / Black
            self::EXPIRED => '#adb5bd',     // Muted / Light Gray
        };
    }

    /**
     * Bootstrap badge CSS class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::REQUESTED => 'bg-warning text-dark',
            self::CONFIRMED => 'bg-primary text-white',
            self::REJECTED => 'bg-danger text-white',
            self::CANCELLED => 'bg-secondary text-white',
            self::RESCHEDULED => 'bg-info text-dark',
            self::CHECKED_IN => 'bg-indigo text-white',
            self::IN_SERVICE => 'bg-orange text-white',
            self::COMPLETED => 'bg-success text-white',
            self::NO_SHOW => 'bg-dark text-white',
            self::EXPIRED => 'bg-light text-dark',
        };
    }

    /**
     * Allowed transitions from current status.
     *
     * @return array<AppointmentStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::REQUESTED => [
                self::CONFIRMED,
                self::REJECTED,
                self::CANCELLED,
                self::NO_SHOW,
                self::EXPIRED,
            ],
            self::CONFIRMED => [
                self::CHECKED_IN,
                self::IN_SERVICE,
                self::COMPLETED,
                self::CANCELLED,
                self::RESCHEDULED,
                self::NO_SHOW,
                self::EXPIRED,
            ],
            self::CHECKED_IN => [
                self::IN_SERVICE,
                self::COMPLETED,
                self::CANCELLED,
                self::NO_SHOW,
            ],
            self::IN_SERVICE => [
                self::COMPLETED,
                self::CANCELLED,
            ],
            self::RESCHEDULED => [
                self::CONFIRMED,
                self::CHECKED_IN,
                self::CANCELLED,
            ],
            self::REJECTED => [],
            self::CANCELLED => [],
            self::COMPLETED => [],
            self::NO_SHOW => [],
            self::EXPIRED => [],
        };
    }

    /**
     * Check if transition to target status is valid.
     */
    public function canTransitionTo(AppointmentStatus|string $target): bool
    {
        $targetEnum = is_string($target) ? self::tryFrom($target) : $target;

        if (! $targetEnum) {
            return false;
        }

        return in_array($targetEnum, $this->allowedTransitions(), true);
    }

    /**
     * Whether this status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return empty($this->allowedTransitions());
    }

    /**
     * Whether this appointment is active and occupies provider time.
     */
    public function blocksSchedule(): bool
    {
        return in_array($this, [
            self::REQUESTED,
            self::CONFIRMED,
            self::CHECKED_IN,
            self::IN_SERVICE,
        ], true);
    }

    /**
     * Array of all status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
