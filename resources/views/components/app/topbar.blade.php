@php
    $name = $provider->name() ?? 'Waqty';
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)->implode('');
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp

<header data-topbar class="sticky top-0 z-[1150] flex h-16 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur">
    {{-- Mobile: open sidebar drawer --}}
    <button
        @click="$store.ui.mobileNavOpen = true"
        class="grid size-9 place-items-center rounded-lg text-fg-muted hover:bg-surface-2 lg:hidden"
        aria-label="Open menu"
    >
        <x-icon name="menu" />
    </button>

    {{-- Search / command palette trigger --}}
    <button
        type="button"
        onclick="window.dispatchEvent(new CustomEvent('open-command-palette'))"
        class="hidden items-center gap-2 rounded-lg border border-line bg-surface-2 px-3 py-2 text-sm text-fg-subtle transition hover:border-line-strong sm:flex sm:w-72"
    >
        <x-icon name="search" class="size-4" />
        <span>{{ __('common.search') ?? 'Search' }}</span>
        <kbd class="ms-auto rounded border border-line bg-surface px-1.5 py-0.5 font-mono text-[10px] text-fg-subtle">Ctrl K</kbd>
    </button>

    <div class="ms-auto flex items-center gap-1">
        {{-- Theme toggle --}}
        <div x-data="{ theme: document.documentElement.getAttribute('data-theme') || 'light' }">
            <button
                @click="theme = theme === 'dark' ? 'light' : 'dark'; document.documentElement.setAttribute('data-theme', theme); document.cookie = 'waqty_theme=' + theme + ';path=/;max-age=31536000;samesite=lax'"
                class="grid size-9 place-items-center rounded-lg text-fg-muted hover:bg-surface-2"
                aria-label="{{ __('common.toggleTheme') }}"
            >
                <x-icon name="moon" x-show="theme !== 'dark'" />
                <x-icon name="sun" x-show="theme === 'dark'" x-cloak />
            </button>
        </div>

        {{-- Language toggle --}}
        <form method="POST" action="{{ route('pref.locale') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ $otherLocale }}">
            <button type="submit" class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium text-fg-muted hover:bg-surface-2" aria-label="{{ __('common.language') }}">
                <x-icon name="globe" class="size-4" />
                <span>{{ $otherLocale === 'ar' ? 'ع' : 'EN' }}</span>
            </button>
        </form>

        {{-- Notifications (static) --}}
        <button class="relative grid size-9 place-items-center rounded-lg text-fg-muted hover:bg-surface-2" aria-label="{{ __('common.notifications') }}">
            <x-icon name="bell" />
            <span class="absolute end-2 top-2 size-1.5 rounded-full bg-primary-500"></span>
        </button>

        {{-- User menu --}}
        <div x-data="{ open: false }" @click.outside="open = false" class="relative ms-1">
            <button @click="open = !open" class="flex items-center gap-2 rounded-lg py-1 ps-1 pe-2 hover:bg-surface-2">
                <span class="grid size-8 place-items-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">{{ $initials }}</span>
                <span class="hidden text-sm font-medium text-fg sm:block">{{ $name }}</span>
                <x-icon name="chevron-down" class="hidden size-4 text-fg-subtle sm:block" />
            </button>

            <div x-show="open" x-cloak x-transition.origin.top.end class="absolute end-0 mt-2 w-60 overflow-hidden rounded-xl border border-line bg-surface shadow-lg">
                <div class="border-b border-line px-4 py-3">
                    <p class="truncate text-sm font-semibold text-fg">{{ $name }}</p>
                    <p class="truncate text-xs text-fg-subtle">{{ $provider->email() }}</p>
                </div>
                <a href="/settings/profile" wire:navigate class="block px-4 py-2.5 text-sm text-fg hover:bg-surface-2">{{ __('sidebar.profile') }}</a>
                <a href="/settings" wire:navigate class="block px-4 py-2.5 text-sm text-fg hover:bg-surface-2">{{ __('sidebar.settings') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-error hover:bg-error-light">
                        <x-icon name="log-out" class="size-4" />
                        {{ __('user.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
