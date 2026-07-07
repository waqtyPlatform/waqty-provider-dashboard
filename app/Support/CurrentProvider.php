<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BusinessCategory;
use App\Enums\UserRole;

/**
 * Session-backed accessor for the authenticated provider profile. Populated at
 * login by AuthService (GET /api/provider/auth/me enrichment) and exposed to
 * every view as `$provider`.
 */
final class CurrentProvider
{
    private function profileKey(): string
    {
        return (string) config('waqty.session.provider_profile');
    }

    /** @return array<string, mixed>|null */
    public function profile(): ?array
    {
        return session($this->profileKey());
    }

    public function check(): bool
    {
        return $this->profile() !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->profile(), $key, $default);
    }

    public function name(): ?string
    {
        return $this->get('name');
    }

    public function email(): ?string
    {
        return $this->get('email');
    }

    public function logoUrl(): ?string
    {
        return $this->get('logo_url');
    }

    public function role(): UserRole
    {
        return UserRole::fromNullable($this->get('role', 'admin'));
    }

    public function businessType(): BusinessCategory
    {
        return BusinessCategory::tryFrom((string) $this->get('business_type'))
            ?? BusinessCategory::Salon;
    }

    /** @return array{label:string, customer:string, staff:string, appointment:string, requiresIntake:bool} */
    public function terminology(): array
    {
        return $this->businessType()->terminology();
    }

    /** @return array<int, array<string, mixed>> */
    public function branches(): array
    {
        return (array) $this->get('branches', []);
    }
}
