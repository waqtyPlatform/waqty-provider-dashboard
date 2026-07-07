<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ReturnData;

/**
 * Port of `returnApi` (src/lib/api.ts) — /api/provider/returns.
 */
class ReturnService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  type|status|per_page
     * @return array<int, ReturnData>
     */
    public function returns(array $filters = []): array
    {
        return ReturnData::collect($this->rows($this->api->get('/api/provider/returns', $filters)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ReturnData
    {
        return ReturnData::from($this->api->post('/api/provider/returns', $data));
    }

    public function approve(string $uuid): void
    {
        $this->api->patch("/api/provider/returns/{$uuid}/approve");
    }

    public function reject(string $uuid, string $reason): void
    {
        $this->api->patch("/api/provider/returns/{$uuid}/reject", ['reason' => $reason]);
    }

    /** @return array<int, mixed> */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
