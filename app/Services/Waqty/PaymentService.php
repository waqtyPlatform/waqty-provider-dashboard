<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\PaymentData;

/**
 * Port of `providerApi` booking-payment methods (src/lib/api.ts).
 * Endpoints under /api/provider/payments* and
 * /api/provider/bookings/{bookingUuid}/payments.
 */
class PaymentService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  payment_method|status|from_date|to_date|per_page
     * @return array<int, PaymentData>
     */
    public function list(array $filters = []): array
    {
        return PaymentData::collect($this->rows($this->api->get('/api/provider/payments', $filters)));
    }

    public function get(string $uuid): PaymentData
    {
        return PaymentData::from($this->api->get("/api/provider/payments/{$uuid}"));
    }

    /** @param array<string, mixed> $data */
    public function create(string $bookingUuid, array $data): PaymentData
    {
        return PaymentData::from($this->api->post("/api/provider/bookings/{$bookingUuid}/payments", $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): PaymentData
    {
        return PaymentData::from($this->api->put("/api/provider/payments/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/payments/{$uuid}");
    }

    /**
     * Normalise a response that may be a bare list or a {data, meta} envelope.
     *
     * @return array<int, mixed>
     */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
