<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `WaitlistEntry` interface (src/lib/api.ts). Represents a customer
 * queued for an available slot. Kept lenient — nested customer/service arrive
 * as loose arrays; status is one of waiting|notified|booked|cancelled.
 */
class WaitlistData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        /** @var array<string, mixed>|null {name, phone} */
        public ?array $customer = null,
        /** @var array<string, mixed>|null {name} */
        public ?array $service = null,
        public ?string $branch_uuid = null,
        public ?string $preferred_date = null,
        public ?string $preferred_time = null,
        public ?string $employee_uuid = null,
        public string $status = 'waiting',
        public int $position = 0,
        public ?string $created_at = null,
    ) {}

    public function customerName(): string
    {
        return (string) (data_get($this->customer, 'name') ?? '—');
    }

    public function customerPhone(): ?string
    {
        return data_get($this->customer, 'phone');
    }

    public function serviceName(): string
    {
        return (string) (data_get($this->service, 'name') ?? '—');
    }

    /** "HH:MM" trimmed from a "HH:MM:SS" preferred_time. */
    public function hhmm(): ?string
    {
        return $this->preferred_time ? substr($this->preferred_time, 0, 5) : null;
    }
}
