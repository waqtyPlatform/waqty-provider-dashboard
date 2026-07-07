<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `Service` interface (src/lib/api.ts). `price` is not part of the
 * canonical Service shape — it is the base price resolved from /service-prices
 * (integer minor units) and merged in by ServiceCatalogService for display.
 */
class ServiceData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $name_ar = null,
        public ?string $description = null,
        public ?string $sub_category_uuid = null,
        public ?ServiceCategoryData $sub_category = null,
        public ?int $estimated_duration_minutes = null,
        public ?string $image_url = null,
        public bool $active = true,
        public ?int $price = null,
    ) {}

    public function categoryName(): ?string
    {
        return $this->sub_category?->name;
    }

    public function displayName(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return (string) $this->name_ar;
        }

        return (string) ($this->name ?? '—');
    }
}
