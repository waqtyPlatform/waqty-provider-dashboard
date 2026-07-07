<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Enums\BusinessCategory;

/**
 * Auth flows, ported from `authApi` (src/lib/api.ts) + AuthContext.
 * On success the JWT lives in the encrypted server session (never the browser),
 * and the provider profile is cached for the shell/nav to read.
 */
class AuthService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * Provider password login: authenticate, store the token, then enrich the
     * profile via me() to infer businessType (best-effort — falls back to the
     * login payload's provider object, mirroring AuthContext).
     */
    public function authenticateProvider(string $email, string $password): void
    {
        $data = $this->api->post('/api/provider/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        session([config('waqty.session.provider_token') => $data['token'] ?? null]);

        $profile = $data['provider'] ?? [];
        try {
            $fetched = $this->api->get('/api/provider/auth/me', cache: false);
            if (is_array($fetched)) {
                $profile = $fetched;
            }
        } catch (WaqtyApiException) {
            // Enrichment is optional; proceed with the login payload.
        }

        session([config('waqty.session.provider_profile') => $this->normalizeProfile($profile)]);
    }

    public function authenticateEmployee(string $email, string $password): void
    {
        $data = $this->api->asEmployee()->post('/api/employee/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        session([config('waqty.session.employee_token') => $data['token'] ?? null]);
        session([config('waqty.session.employee_profile') => $data['employee'] ?? []]);
    }

    public function sendOtp(string $email): void
    {
        $this->api->post('/api/provider/auth/send-otp', ['email' => $email]);
    }

    public function verifyOtp(string $email, string $otp): bool
    {
        $data = $this->api->post('/api/provider/auth/verify-otp', ['email' => $email, 'otp' => $otp]);

        return (bool) ($data['valid'] ?? false);
    }

    public function resetPassword(string $email, string $otp, string $newPassword): void
    {
        $this->api->post('/api/provider/auth/reset-password', [
            'email' => $email,
            'otp' => $otp,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ]);
    }

    public function logout(): void
    {
        try {
            $this->api->post('/api/provider/auth/logout');
        } catch (WaqtyApiException) {
            // Best-effort — clear the local session regardless.
        }

        session()->forget([
            config('waqty.session.provider_token'),
            config('waqty.session.provider_profile'),
        ]);
    }

    /**
     * Reduce a raw provider profile to the shell-facing session shape, deriving
     * businessType from category.name and defaulting role to admin.
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function normalizeProfile(array $profile): array
    {
        $categoryName = data_get($profile, 'category.name');

        return [
            'uuid' => data_get($profile, 'uuid'),
            'name' => data_get($profile, 'name'),
            'email' => data_get($profile, 'email'),
            'phone' => data_get($profile, 'phone'),
            'logo_url' => data_get($profile, 'logo_url'),
            'category' => data_get($profile, 'category'),
            'branches' => data_get($profile, 'branches', []),
            'role' => data_get($profile, 'role', 'admin'),
            'business_type' => BusinessCategory::normalize(is_string($categoryName) ? $categoryName : null)->value,
        ];
    }
}
