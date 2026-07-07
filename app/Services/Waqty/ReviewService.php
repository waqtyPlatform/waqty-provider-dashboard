<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ProviderRatingData;

/**
 * Review moderation (src/lib/api.ts) — GET /api/provider/ratings plus the
 * /api/provider/reviews/{uuid}/{flag|hide|respond} moderation actions.
 */
class ReviewService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  status|rating|employee_uuid|branch_uuid|per_page
     * @return array<int, ProviderRatingData>
     */
    public function ratings(array $filters = []): array
    {
        return ProviderRatingData::collect($this->rows($this->api->get('/api/provider/ratings', $filters)));
    }

    public function flag(string $uuid, string $reason): void
    {
        $this->api->patch("/api/provider/reviews/{$uuid}/flag", ['reason' => $reason]);
    }

    public function hide(string $uuid): void
    {
        $this->api->patch("/api/provider/reviews/{$uuid}/hide");
    }

    public function respond(string $uuid, string $response): void
    {
        $this->api->patch("/api/provider/reviews/{$uuid}/respond", ['response' => $response]);
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
