<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve locale: session -> `waqty_language` cookie -> Accept-Language -> en.
 * The same value drives both <html lang/dir> and the API `Accept-Language` header.
 */
class SetLocale
{
    private const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session(config('waqty.session.locale'))
            ?? $request->cookie('waqty_language')
            ?? $request->getPreferredLanguage(self::SUPPORTED)
            ?? 'en';

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
