<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `Employee` interface (src/lib/api.ts).
 */
class EmployeeData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $branch_uuid = null,
        public ?BranchData $branch = null,
        public bool $active = true,
        public bool $blocked = false,
        // Non-canonical enrichments some responses/mocks include.
        public ?string $role = null,
        public ?string $position = null,
        public ?float $rating = null,
        public ?string $created_at = null,
    ) {}

    public function branchName(): ?string
    {
        return $this->branch?->name;
    }

    public function initials(): string
    {
        return $this->name ?? '?';
    }
}
