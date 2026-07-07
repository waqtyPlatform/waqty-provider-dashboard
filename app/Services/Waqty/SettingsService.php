<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\BusinessHoursData;
use App\Data\Waqty\PaymentMethodData;
use App\Data\Waqty\PettyCashItemData;
use App\Data\Waqty\ResourceData;
use App\Data\Waqty\SafeData;

/**
 * Provider settings (src/lib/api.ts settingsApi) — /api/provider/settings/*.
 * Covers the API-backed settings surfaces (hours, payment methods, …); local
 * preference pages persist to cookies/session and don't route through here.
 */
class SettingsService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, BusinessHoursData> */
    public function businessHours(): array
    {
        return BusinessHoursData::collect($this->rows($this->api->get('/api/provider/settings/business-hours')));
    }

    /** @param array<int, array<string, mixed>> $days */
    public function updateBusinessHours(array $days): void
    {
        $this->api->put('/api/provider/settings/business-hours', ['days' => $days]);
    }

    /** @return array<int, PaymentMethodData> */
    public function paymentMethods(): array
    {
        return PaymentMethodData::collect($this->rows($this->api->get('/api/provider/settings/payment-methods')));
    }

    /** @param array<string, mixed> $data */
    public function createPaymentMethod(array $data): PaymentMethodData
    {
        return PaymentMethodData::from($this->api->post('/api/provider/settings/payment-methods', $data));
    }

    /** @param array<string, mixed> $data */
    public function updatePaymentMethod(string $uuid, array $data): PaymentMethodData
    {
        return PaymentMethodData::from($this->api->put("/api/provider/settings/payment-methods/{$uuid}", $data));
    }

    public function deletePaymentMethod(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/payment-methods/{$uuid}");
    }

    /** @return array<int, array<string, mixed>> a list of {type, push, email} rows */
    public function notificationSettings(): array
    {
        return $this->rows($this->api->get('/api/provider/settings/notifications'));
    }

    /** @param array<int, array<string, mixed>> $settings */
    public function updateNotificationSettings(array $settings): void
    {
        $this->api->put('/api/provider/settings/notifications', ['settings' => $settings]);
    }

    /** @return array<string, mixed> */
    public function invoiceSettings(): array
    {
        return $this->row($this->api->get('/api/provider/settings/invoice'));
    }

    /** @param array<string, mixed> $data */
    public function updateInvoiceSettings(array $data): void
    {
        $this->api->put('/api/provider/settings/invoice', $data);
    }

    /** @return array<string, mixed> */
    public function tippingSettings(): array
    {
        return $this->row($this->api->get('/api/provider/settings/tipping'));
    }

    /** @param array<string, mixed> $data */
    public function updateTippingSettings(array $data): void
    {
        $this->api->put('/api/provider/settings/tipping', $data);
    }

    /** @return array<string, mixed> */
    public function loyaltySettings(): array
    {
        return $this->row($this->api->get('/api/provider/settings/loyalty'));
    }

    /** @param array<string, mixed> $data */
    public function updateLoyaltySettings(array $data): void
    {
        $this->api->put('/api/provider/settings/loyalty', $data);
    }

    /** @return array<int, SafeData> */
    public function safes(): array
    {
        return SafeData::collect($this->rows($this->api->get('/api/provider/settings/safes')));
    }

    /** @param array<string, mixed> $data */
    public function createSafe(array $data): SafeData
    {
        return SafeData::from($this->api->post('/api/provider/settings/safes', $data));
    }

    /** @param array<string, mixed> $data */
    public function updateSafe(string $uuid, array $data): SafeData
    {
        return SafeData::from($this->api->put("/api/provider/settings/safes/{$uuid}", $data));
    }

    public function deleteSafe(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/safes/{$uuid}");
    }

    /** @return array<int, ResourceData> */
    public function resources(): array
    {
        return ResourceData::collect($this->rows($this->api->get('/api/provider/settings/resources')));
    }

    /** @param array<string, mixed> $data */
    public function createResource(array $data): ResourceData
    {
        return ResourceData::from($this->api->post('/api/provider/settings/resources', $data));
    }

    /** @param array<string, mixed> $data */
    public function updateResource(string $uuid, array $data): ResourceData
    {
        return ResourceData::from($this->api->put("/api/provider/settings/resources/{$uuid}", $data));
    }

    public function deleteResource(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/resources/{$uuid}");
    }

    /** @return array<int, PettyCashItemData> */
    public function pettyCashItems(): array
    {
        return PettyCashItemData::collect($this->rows($this->api->get('/api/provider/settings/petty-cash-items')));
    }

    /** @param array<string, mixed> $data */
    public function createPettyCashItem(array $data): PettyCashItemData
    {
        return PettyCashItemData::from($this->api->post('/api/provider/settings/petty-cash-items', $data));
    }

    /** @param array<string, mixed> $data */
    public function updatePettyCashItem(string $uuid, array $data): PettyCashItemData
    {
        return PettyCashItemData::from($this->api->put("/api/provider/settings/petty-cash-items/{$uuid}", $data));
    }

    public function deletePettyCashItem(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/petty-cash-items/{$uuid}");
    }

    /** @return array<int, mixed> */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Unwrap a single settings object, tolerating a `{data: {...}}` envelope.
     *
     * @return array<string, mixed>
     */
    private function row(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
