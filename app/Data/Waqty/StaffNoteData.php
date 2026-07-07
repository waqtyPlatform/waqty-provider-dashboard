<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `StaffNote` (src/lib/api.ts). Endpoint:
 * /api/provider/customers/{uuid}/staff-notes.
 */
class StaffNoteData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $customer_uuid = null,
        public ?string $employee_uuid = null,
        public ?EmployeeData $employee = null,
        public ?string $note = null,
        public ?ServiceData $service = null,
        public ?string $created_at = null,
    ) {}

    public function employeeName(): ?string
    {
        return $this->employee?->name;
    }
}
