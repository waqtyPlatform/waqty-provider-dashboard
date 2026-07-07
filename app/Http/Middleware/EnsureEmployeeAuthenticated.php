<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(session(config('waqty.session.employee_token')))) {
            return redirect()->route('employee-portal.login');
        }

        return $next($request);
    }
}
