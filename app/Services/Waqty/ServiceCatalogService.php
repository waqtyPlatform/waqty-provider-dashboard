<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ServiceCategoryData;
use App\Data\Waqty\ServiceData;
use App\Data\Waqty\ServicePriceData;

/**
 * Port of `providerApi` service-catalog methods (src/lib/api.ts:1005-1054).
 * Endpoints under /api/provider/services*, /service-prices*, and
 * /settings/service-categories.
 */
class ServiceCatalogService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * List services with their base price merged in (integer minor units).
     *
     * @param  array<string, mixed>  $filters
     * @param  bool  $withPrices  fetch /service-prices and merge base price per service
     * @return array<int, ServiceData>
     */
    public function services(array $filters = [], bool $withPrices = true): array
    {
        $services = ServiceData::collect($this->rows($this->api->get('/api/provider/services', $filters)));

        if (! $withPrices) {
            return $services;
        }

        $baseByService = $this->basePriceMap();

        return array_map(function (ServiceData $s) use ($baseByService) {
            $s->price = $baseByService[$s->uuid] ?? $s->price;

            return $s;
        }, $services);
    }

    public function service(string $uuid): ServiceData
    {
        return ServiceData::from($this->api->get("/api/provider/services/{$uuid}"));
    }

    /**
     * Create a service (multipart — the source posts FormData incl. an image).
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $files  ['image' => UploadedFile]
     */
    public function createService(array $fields, array $files = []): ServiceData
    {
        return ServiceData::from($this->api->postFormData('/api/provider/services', $fields, $files));
    }

    /**
     * Update a service (source uses POST multipart to /services/{uuid}).
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $files
     */
    public function updateService(string $uuid, array $fields, array $files = []): ServiceData
    {
        return ServiceData::from($this->api->postFormData("/api/provider/services/{$uuid}", $fields, $files));
    }

    public function deleteService(string $uuid): void
    {
        $this->api->delete("/api/provider/services/{$uuid}");
    }

    public function toggleActive(string $uuid, bool $active): void
    {
        $this->api->patch("/api/provider/services/{$uuid}/active", ['active' => $active]);
    }

    /** @return array<int, ServicePriceData> */
    public function servicePrices(): array
    {
        return ServicePriceData::collect($this->rows($this->api->get('/api/provider/service-prices')));
    }

    /**
     * Create or update a scoped price row (branch/employee/pricing-group or base).
     * The source posts to /service-prices and PUTs /service-prices/{uuid}.
     *
     * @param  array<string, mixed>  $payload
     */
    public function upsertServicePrice(array $payload): ServicePriceData
    {
        $uuid = $payload['uuid'] ?? null;
        unset($payload['uuid']);

        return ServicePriceData::from($uuid
            ? $this->api->put("/api/provider/service-prices/{$uuid}", $payload)
            : $this->api->post('/api/provider/service-prices', $payload));
    }

    public function deleteServicePrice(string $uuid): void
    {
        $this->api->delete("/api/provider/service-prices/{$uuid}");
    }

    /** @return array<int, ServiceCategoryData> */
    public function categories(): array
    {
        return ServiceCategoryData::collect($this->rows($this->api->get('/api/provider/settings/service-categories')));
    }

    /** @param array<string, mixed> $data */
    public function createCategory(array $data): ServiceCategoryData
    {
        return ServiceCategoryData::from($this->api->post('/api/provider/settings/service-categories', $data));
    }

    /** @param array<string, mixed> $data */
    public function updateCategory(string $uuid, array $data): ServiceCategoryData
    {
        return ServiceCategoryData::from($this->api->put("/api/provider/settings/service-categories/{$uuid}", $data));
    }

    public function deleteCategory(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/service-categories/{$uuid}");
    }

    /**
     * Service→employee assignment matrix rows ({service_uuid, employee_uuid, active}).
     *
     * @return array<int, array<string, mixed>>
     */
    public function serviceEmployees(): array
    {
        return $this->rows($this->api->get('/api/provider/settings/service-employees'));
    }

    /**
     * Persist the full assignment matrix in one PUT.
     *
     * @param  array<int, array{service_uuid:string, employee_uuid:string, active:bool}>  $mappings
     */
    public function saveServiceEmployees(array $mappings): void
    {
        $this->api->put('/api/provider/settings/service-employees', ['mappings' => $mappings]);
    }

    /**
     * Map service_uuid => base price (the price row with no branch/employee/
     * pricing-group scope — priority 6 of the resolution cascade).
     *
     * @return array<string, int>
     */
    private function basePriceMap(): array
    {
        $map = [];

        try {
            foreach ($this->servicePrices() as $price) {
                $isBase = blank($price->branch_uuid) && blank($price->employee_uuid) && blank($price->pricing_group_uuid);
                if ($isBase && $price->service_uuid !== null && ! isset($map[$price->service_uuid])) {
                    $map[$price->service_uuid] = $price->price;
                }
            }
        } catch (WaqtyApiException) {
            // Pricing endpoint unavailable — services still render without a price.
        }

        return $map;
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
