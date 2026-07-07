<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\PricingGroupData;

/**
 * Pricing groups — /api/provider/pricing-groups.
 * Groups employees so shared service-price tiers apply across the group.
 */
class PricingGroupService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, PricingGroupData> */
    public function list(): array
    {
        return PricingGroupData::collect($this->rows($this->api->get('/api/provider/pricing-groups')));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PricingGroupData
    {
        return PricingGroupData::from($this->api->post('/api/provider/pricing-groups', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): PricingGroupData
    {
        return PricingGroupData::from($this->api->put("/api/provider/pricing-groups/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/pricing-groups/{$uuid}");
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
