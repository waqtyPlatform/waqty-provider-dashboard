<?php

use App\Http\Middleware\EnforceRouteRestrictions;
use App\Http\Middleware\EnsureEmployeeAuthenticated;
use App\Http\Middleware\EnsureProviderAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Locale resolution runs for every web request.
        $middleware->web(append: [SetLocale::class]);

        $middleware->alias([
            'waqty.provider' => EnsureProviderAuthenticated::class,
            'waqty.employee' => EnsureEmployeeAuthenticated::class,
            'waqty.roles' => EnforceRouteRestrictions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
