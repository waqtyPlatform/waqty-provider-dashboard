<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentProvider;
use App\Support\RouteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate (port of RoleGuard's ROLE_RESTRICTED_ROUTES). Redirects unauthorised
 * roles to /?unauthorized=1, exactly like the source client guard.
 */
class EnforceRouteRestrictions
{
    public function __construct(private readonly CurrentProvider $provider) {}

    public function handle(Request $request, Closure $next): Response
    {
        $role = $this->provider->role()->value;

        if (! RouteAccess::allowed($request->path(), $role)) {
            return redirect('/?unauthorized=1');
        }

        return $next($request);
    }
}
