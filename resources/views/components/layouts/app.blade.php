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

    {{-- No-FOUC theme resolution for the "system" preference (applied before paint). --}}
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
    <x-app.sidebar />

    <div
        x-data
        :class="$store.ui.sidebarCollapsed ? 'lg:ps-[72px]' : 'lg:ps-[260px]'"
        class="flex min-h-screen flex-col transition-[padding] duration-200"
    >
        <x-app.topbar />

        <main class="mx-auto w-full max-w-[1440px] flex-1 pb-20 lg:pb-0">
            {{ $slot }}
        </main>
    </div>

    <x-app.mobile-bottom-nav />
    <x-app.mobile-nav-drawer />
    <livewire:app.command-palette />
    <livewire:app.toasts />

    @livewireScripts
</body>
</html>
