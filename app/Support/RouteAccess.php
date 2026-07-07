<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Client-side RBAC port of findRestrictedMatch / isRouteAllowedForRole
 * (src/components/RoleGuard.tsx). Backed by config/waqty_roles.php.
 */
final class RouteAccess
{
    /** @return array<int, string>|null required roles, or null when unrestricted */
    public static function restrictedRoles(string $path): ?array
    {
        $path = '/'.ltrim($path, '/');

        foreach (config('waqty_roles', []) as $route => $roles) {
            if ($path === $route || str_starts_with($path, $route.'/')) {
                return $roles;
            }
        }

        return null;
    }

    public static function allowed(string $path, ?string $role): bool
    {
        $required = self::restrictedRoles($path);

        return $required === null || in_array($role, $required, true);
    }
}
