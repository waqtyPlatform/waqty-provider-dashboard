@php($locale = app()->getLocale())
@php($theme = request()->cookie('waqty_theme', 'system'))
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', $locale) }}"
    dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
    data-theme="{{ in_array($theme, ['light', 'dark']) ? $theme : 'light' }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Waqty') }}</title>

    <script>
        (function () {
            var t = document.cookie.match(/(?:^|;\s*)waqty_theme=([^;]+)/);
            t = t ? decodeURIComponent(t[1]) : 'system';
            if (t === 'system') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-2 text-fg antialiased">
    <main class="grid min-h-screen place-items-center p-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
