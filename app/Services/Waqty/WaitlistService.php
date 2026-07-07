<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\WaitlistData;

/**
 * Port of `providerApi` waitlist methods. Endpoints under
 * /api/provider/waitlist*. Entries are queued customers awaiting a free slot.
 */
class WaitlistService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  status
     * @return array<int, WaitlistData>
     */
    public function list(array $filters = []): array
    {
        return WaitlistData::collect($this->rows($this->api->get('/api/provider/waitlist', $filters)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): WaitlistData
    {
        return WaitlistData::from($this->api->post('/api/provider/waitlist', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): WaitlistData
    {
        return WaitlistData::from($this->api->put("/api/provider/waitlist/{$uuid}", $data));
    }

    public function remove(string $uuid): void
    {
        $this->api->delete("/api/provider/waitlist/{$uuid}");
    }

    public function notify(string $uuid): void
    {
        $this->api->patch("/api/provider/waitlist/{$uuid}/notify");
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
