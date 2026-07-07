<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edge auth gate (port of src/middleware.ts): requires a provider token in the
 * session. No role checks here — those are enforced by EnforceRouteRestrictions
 * and, ultimately, the backend API.
 */
class EnsureProviderAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(session(config('waqty.session.provider_token')))) {
            return redirect()->route('login', ['redirect' => $request->path()]);
        }

        return $next($request);
    }
}
