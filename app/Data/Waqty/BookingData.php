<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use App\Enums\BookingStatus;
use Spatie\LaravelData\Data;

/**
 * Port of the `Booking` interface (src/lib/api.ts). `price` is integer minor
 * units. The live API returns single-service bookings (one service each), so a
 * booking maps to exactly one calendar block.
 */
class BookingData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $branch_uuid = null,
        public ?BranchData $branch = null,
        public ?string $service_uuid = null,
        public ?ServiceData $service = null,
        public ?string $employee_uuid = null,
        public ?EmployeeData $employee = null,
        /** @var array<string, mixed>|null {uuid,name,email,phone} */
        public ?array $user = null,
        public ?string $booking_date = null,
        public ?string $start_time = null,
        public ?string $end_time = null,
        public string $status = 'pending',
        public ?string $payment_status = null,
        public ?int $price = null,
        public ?string $notes = null,
    ) {}

    public function statusEnum(): BookingStatus
    {
        return BookingStatus::tryFrom($this->status) ?? BookingStatus::Pending;
    }

    public function clientName(): string
    {
        return (string) (data_get($this->user, 'name') ?? '—');
    }

    public function clientPhone(): ?string
    {
        return data_get($this->user, 'phone');
    }

    public function serviceName(): string
    {
        return (string) ($this->service?->displayName() ?? '—');
    }

    public function employeeName(): ?string
    {
        return $this->employee?->name;
    }

    /** "HH:MM" trimmed from a "HH:MM:SS" start_time. */
    public function hhmm(): ?string
    {
        return $this->start_time ? substr($this->start_time, 0, 5) : null;
    }

    public function endHhmm(): ?string
    {
        return $this->end_time ? substr($this->end_time, 0, 5) : null;
    }

    /** Duration in minutes: end−start if both known, else the service estimate, else 30. */
    public function durationMinutes(): int
    {
        if ($this->start_time && $this->end_time) {
            $start = $this->minutesOfDay($this->start_time);
            $end = $this->minutesOfDay($this->end_time);
            if ($end > $start) {
                return $end - $start;
            }
        }

        return $this->service?->estimated_duration_minutes ?? 30;
    }

    private function minutesOfDay(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }
}
