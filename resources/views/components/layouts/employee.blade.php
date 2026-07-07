@php($locale = app()->getLocale())
@php($theme = request()->cookie('waqty_theme', 'system'))
@php($profile = session(config('waqty.session.employee_profile'), []))
@php($empName = $profile['name'] ?? 'Employee')
@php($otherLocale = $locale === 'ar' ? 'en' : 'ar')
@php($initials = collect(explode(' ', trim($empName)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode(''))
@php($path = request()->path())
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
    <title>{{ $title ?? __('portal.title') }}</title>

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
    <header class="sticky top-0 z-[1150] border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-4xl items-center gap-3 px-4">
            <div class="flex items-center gap-2">
                <span class="grid size-8 place-items-center rounded-lg bg-primary-500 text-sm font-bold text-white">و</span>
                <span class="text-sm font-semibold text-fg">{{ __('portal.title') }}</span>
            </div>

            <div class="ms-auto flex items-center gap-1">
                {{-- Theme toggle --}}
                <div x-data="{ theme: document.documentElement.getAttribute('data-theme') || 'light' }">
                    <button
                        @click="theme = theme === 'dark' ? 'light' : 'dark'; document.documentElement.setAttribute('data-theme', theme); document.cookie = 'waqty_theme=' + theme + ';path=/;max-age=31536000;samesite=lax'"
                        class="grid size-9 place-items-center rounded-lg text-fg-muted hover:bg-surface-2" aria-label="Toggle theme">
                        <x-icon name="moon" x-show="theme !== 'dark'" />
                        <x-icon name="sun" x-show="theme === 'dark'" x-cloak />
                    </button>
                </div>

                {{-- Language toggle --}}
                <form method="POST" action="{{ route('pref.locale') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $otherLocale }}">
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium text-fg-muted hover:bg-surface-2" aria-label="Change language">
                        <x-icon name="globe" class="size-4" />
                        <span>{{ $otherLocale === 'ar' ? 'ع' : 'EN' }}</span>
                    </button>
                </form>

                {{-- Employee menu --}}
                <div x-data="{ open: false }" @click.outside="open = false" class="relative ms-1">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-lg py-1 ps-1 pe-2 hover:bg-surface-2">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">{{ $initials ?: 'E' }}</span>
                        <span class="hidden text-sm font-medium text-fg sm:block">{{ $empName }}</span>
                        <x-icon name="chevron-down" class="hidden size-4 text-fg-subtle sm:block" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.end class="absolute end-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-surface shadow-lg">
                        <div class="border-b border-line px-4 py-3">
                            <p class="truncate text-sm font-semibold text-fg">{{ $empName }}</p>
                            <p class="truncate text-xs text-fg-subtle">{{ $profile['email'] ?? '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('employee-portal.logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-error hover:bg-error-light">
                                <x-icon name="log-out" class="size-4" />{{ __('user.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Portal nav --}}
        <nav class="mx-auto flex max-w-4xl gap-1 px-4">
            @foreach ([
                'employee-portal/dashboard' => ['portal.navDashboard', 'layout-dashboard'],
                'employee-portal/attendance' => ['portal.navAttendance', 'clock'],
                'employee-portal/shifts' => ['portal.navShifts', 'calendar-days'],
            ] as $href => $meta)
                <a href="/{{ $href }}" wire:navigate
                    @class([
                        '-mb-px flex items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-medium transition',
                        'border-primary-500 text-primary-600' => $path === $href,
                        'border-transparent text-fg-muted hover:text-fg' => $path !== $href,
                    ])>
                    <x-icon :name="$meta[1]" class="size-4" />{{ __($meta[0]) }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-4xl flex-1 px-1 pb-16">
        {{ $slot }}
    </main>

    <livewire:app.toasts />
    @livewireScripts
</body>
</html>
