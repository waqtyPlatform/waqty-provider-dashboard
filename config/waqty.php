<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Waqty External REST API
    |--------------------------------------------------------------------------
    |
    | This dashboard owns no local domain database. It is a server-side client
    | of the external Waqty REST API (JWT bearer auth). Provider endpoints live
    | under `{base_url}/api/provider/*`, employee under `/api/employee/*`, and
    | public catalog under `/api/public/*`.
    |
    */

    'base_url' => rtrim(env('WAQTY_API_BASE_URL', 'https://waqty.alemtayaz.shop/public'), '/'),

    // Hard request timeout in seconds (source API client uses 15s).
    'timeout' => (int) env('WAQTY_API_TIMEOUT', 15),

    // Short-lived GET de-dup cache TTL in seconds (source uses a 5s window).
    'get_cache_ttl' => (int) env('WAQTY_GET_CACHE_TTL', 5),

    // Session keys for the two token surfaces.
    'session' => [
        'provider_token' => 'waqty.provider.token',
        'provider_profile' => 'waqty.provider.profile',
        'employee_token' => 'waqty.employee.token',
        'employee_profile' => 'waqty.employee.profile',
        'locale' => 'waqty.locale',
        'theme' => 'waqty.theme',
    ],

    // Surfaced on the Help page contact card.
    'support' => [
        'email' => env('WAQTY_SUPPORT_EMAIL', 'support@waqty.com'),
        'whatsapp' => env('WAQTY_SUPPORT_WHATSAPP', '201000000000'),
    ],

    // Local-only provider credential for previewing the login form without API access.
    'local_login' => [
        'enabled' => (bool) env('WAQTY_LOCAL_LOGIN_ENABLED', false),
        'email' => env('WAQTY_LOCAL_LOGIN_EMAIL', 'test@waqty.local'),
        'password' => env('WAQTY_LOCAL_LOGIN_PASSWORD', 'password123'),
        'business_type' => env('WAQTY_LOCAL_LOGIN_BUSINESS_TYPE', 'salon'),
    ],

];
