<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\BookingActivityData;
use App\Data\Waqty\BookingData;

/**
 * Port of `providerApi` booking methods (src/lib/api.ts:1057-1176, 2706-2708).
 * Endpoints under /api/provider/bookings*.
 */
class BookingService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  status|branch_uuid|employee_uuid|booking_date|from_date|to_date|per_page
     * @return array<int, BookingData>
     */
    public function list(array $filters = []): array
    {
        return BookingData::collect($this->rows($this->api->get('/api/provider/bookings', $filters)));
    }

    public function get(string $uuid): BookingData
    {
        return BookingData::from($this->api->get("/api/provider/bookings/{$uuid}"));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): BookingData
    {
        return BookingData::from($this->api->post('/api/provider/bookings', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): BookingData
    {
        return BookingData::from($this->api->put("/api/provider/bookings/{$uuid}", $data));
    }

    public function setStatus(string $uuid, string $status): void
    {
        $this->api->patch("/api/provider/bookings/{$uuid}/status", ['status' => $status]);
    }

    public function advance(string $uuid): BookingData
    {
        return BookingData::from($this->api->post("/api/provider/bookings/{$uuid}/advance"));
    }

    public function cancel(string $uuid, ?string $reason = null): void
    {
        $this->api->post("/api/provider/bookings/{$uuid}/cancel", $reason ? ['cancellation_reason' => $reason] : []);
    }

    /** @return array<int, BookingActivityData> */
    public function activities(string $uuid): array
    {
        return BookingActivityData::collect($this->rows($this->api->get("/api/provider/bookings/{$uuid}/activities")));
    }

    public function nextUpcoming(): ?BookingData
    {
        $data = $this->api->get('/api/provider/bookings/next-upcoming');

        return is_array($data) && $data !== [] ? BookingData::from($data) : null;
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
