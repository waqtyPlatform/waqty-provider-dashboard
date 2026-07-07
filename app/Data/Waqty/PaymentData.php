<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `BookingPayment` interface (src/lib/api.ts). `amount` is integer
 * minor units. A payment is attached to a booking; the (optional) nested
 * `booking` carries its date and service name for display.
 */
class PaymentData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $booking_uuid = null,
        /** @var array<string, mixed>|null {uuid, booking_date, service:{name}} */
        public ?array $booking = null,
        public string $payment_method = 'cash',
        public ?int $amount = null,
        public string $status = 'pending',
        public ?string $transaction_id = null,
        public ?string $notes = null,
        public ?string $created_at = null,
    ) {}

    public function serviceName(): string
    {
        return (string) (data_get($this->booking, 'service.name') ?? '—');
    }

    public function bookingDate(): ?string
    {
        return data_get($this->booking, 'booking_date');
    }
}
