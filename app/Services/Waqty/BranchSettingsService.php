<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\BranchData;

/**
 * Branch CRUD (src/lib/api.ts branchesApi) — /api/provider/branches.
 * Dedicated service mirroring SettingsService: it unwraps the `{data}`
 * envelope via {@see rows()} and returns {@see BranchData} DTOs.
 */
class BranchSettingsService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, BranchData> */
    public function list(): array
    {
        return BranchData::collect($this->rows($this->api->get('/api/provider/branches')));
    }

    public function get(string $uuid): BranchData
    {
        return BranchData::from($this->api->get("/api/provider/branches/{$uuid}"));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): BranchData
    {
        return BranchData::from($this->api->post('/api/provider/branches', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): BranchData
    {
        return BranchData::from($this->api->put("/api/provider/branches/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/branches/{$uuid}");
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
