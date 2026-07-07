<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\CustomerData;
use App\Data\Waqty\CustomerGroupData;
use App\Data\Waqty\CustomerReviewData;
use App\Data\Waqty\CustomerStatementData;
use App\Data\Waqty\StaffNoteData;

/**
 * Port of `customerApi` (src/lib/api.ts). Endpoints under /api/provider/customers*.
 */
class CustomerService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, CustomerData>
     */
    public function customers(array $filters = []): array
    {
        return CustomerData::collect($this->rows($this->api->get('/api/provider/customers', $filters)));
    }

    public function customer(string $uuid): CustomerData
    {
        return CustomerData::from($this->api->get("/api/provider/customers/{$uuid}"));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomerData
    {
        return CustomerData::from($this->api->post('/api/provider/customers', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): CustomerData
    {
        return CustomerData::from($this->api->put("/api/provider/customers/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/customers/{$uuid}");
    }

    /** @return array<int, CustomerGroupData> */
    public function groups(): array
    {
        return CustomerGroupData::collect($this->rows($this->api->get('/api/provider/customer-groups')));
    }

    /** @param array<string, mixed> $data */
    public function createGroup(array $data): CustomerGroupData
    {
        return CustomerGroupData::from($this->api->post('/api/provider/customer-groups', $data));
    }

    /** @param array<string, mixed> $data */
    public function updateGroup(string $uuid, array $data): CustomerGroupData
    {
        return CustomerGroupData::from($this->api->put("/api/provider/customer-groups/{$uuid}", $data));
    }

    public function deleteGroup(string $uuid): void
    {
        $this->api->delete("/api/provider/customer-groups/{$uuid}");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, CustomerStatementData>
     */
    public function statements(string $uuid, array $filters = []): array
    {
        return CustomerStatementData::collect($this->rows($this->api->get("/api/provider/customers/{$uuid}/statements", $filters)));
    }

    /** @return array<int, CustomerReviewData> */
    public function reviews(string $uuid): array
    {
        return CustomerReviewData::collect($this->rows($this->api->get("/api/provider/customers/{$uuid}/reviews")));
    }

    /** @return array<int, StaffNoteData> */
    public function staffNotes(string $uuid): array
    {
        return StaffNoteData::collect($this->rows($this->api->get("/api/provider/customers/{$uuid}/staff-notes")));
    }

    /** @param array<string, mixed> $data */
    public function createStaffNote(string $uuid, array $data): StaffNoteData
    {
        return StaffNoteData::from($this->api->post("/api/provider/customers/{$uuid}/staff-notes", $data));
    }

    public function deleteStaffNote(string $uuid, string $noteUuid): void
    {
        $this->api->delete("/api/provider/customers/{$uuid}/staff-notes/{$noteUuid}");
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
