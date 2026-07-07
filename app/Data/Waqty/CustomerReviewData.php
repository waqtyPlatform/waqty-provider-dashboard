<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `CustomerReview` (src/lib/api.ts). Endpoints under /api/provider/reviews
 * and /api/provider/customers/{uuid}/reviews.
 */
class CustomerReviewData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $customer_uuid = null,
        public ?EmployeeData $employee = null,
        public ?ServiceData $service = null,
        public ?string $booking_uuid = null,
        public int $rating = 0,
        public ?string $comment = null,
        public ?string $response = null,
        public string $status = 'published',
        public string $direction = 'by_customer',
        public ?string $created_at = null,
    ) {}

    public function serviceName(): ?string
    {
        return $this->service?->name;
    }

    public function employeeName(): ?string
    {
        return $this->employee?->name;
    }
}
