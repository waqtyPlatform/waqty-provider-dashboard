@php
    use App\Services\NavigationService;

    $nav = app(NavigationService::class)->build();
    $current = '/'.trim(request()->path(), '/');
    if ($current === '/'.'') { $current = '/'; }

    $allHrefs = [];
    foreach (array_merge($nav['primary'], $nav['footer']) as $g) {
        if (! empty($g['href'])) { $allHrefs[] = $g['href']; }
        foreach ($g['children'] ?? [] as $c) { if (! empty($c['href'])) { $allHrefs[] = $c['href']; } }
    }

    $isActive = function (string $href) use ($current, $allHrefs): bool {
        if ($current === $href) { return true; }
        if ($href === '/') { return false; }
        $prefix = rtrim($href, '/').'/';
        if (! str_starts_with($current, $prefix)) { return false; }
        return ! (in_array($current, $allHrefs, true) && $current !== $href);
    };

    $openGroups = [];
    foreach (array_merge($nav['primary'], $nav['footer']) as $g) {
        $on = (! empty($g['href']) && $isActive($g['href']));
        foreach ($g['children'] ?? [] as $c) { $on = $on || (! empty($c['href']) && $isActive($c['href'])); }
        if ($on) { $openGroups[$g['id']] = true; }
    }

    $groups = array_merge($nav['primary'], [['divider' => true]], $nav['footer']);
@endphp

<div
    x-data="{ open: @js($openGroups), toggle(id) { this.open[id] = ! this.open[id] } }"
    x-show="$store.ui.mobileNavOpen"
    x-cloak
    @keydown.escape.window="$store.ui.mobileNavOpen = false"
    class="fixed inset-0 z-[1200] lg:hidden"
>
    <div class="absolute inset-0 bg-overlay" @click="$store.ui.mobileNavOpen = false" x-show="$store.ui.mobileNavOpen" x-transition.opacity></div>

    <aside
        x-show="$store.ui.mobileNavOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full rtl:translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full rtl:translate-x-full"
        class="absolute inset-y-0 start-0 flex w-72 max-w-[82%] flex-col bg-sidebar text-fg-sidebar shadow-xl"
        @click="if ($event.target.closest('a')) $store.ui.mobileNavOpen = false"
    >
        <div class="flex h-16 items-center gap-2.5 px-4">
            <div class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-500 text-lg font-bold text-white">و</div>
            <span class="text-lg font-semibold text-white">Waqty</span>
            <button @click="$store.ui.mobileNavOpen = false" class="ms-auto grid size-8 place-items-center rounded-lg text-fg-sidebar hover:bg-sidebar-hover hover:text-white" aria-label="{{ __('common.close') }}">
                <x-icon name="x" class="size-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-2">
            @foreach ($groups as $group)
                @if (! empty($group['divider']))
                    <div class="my-3 h-px bg-white/5"></div>
                @elseif (empty($group['children']))
                    <a href="{{ $group['href'] }}" wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-primary-500 text-white' => $isActive($group['href']),
                            'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $isActive($group['href']),
                        ])>
                        <x-icon :name="$group['icon']" class="size-5 shrink-0" />
                        <span class="truncate">{{ $group['label'] }}</span>
                    </a>
                @else
                    @php $childActive = collect($group['children'])->contains(fn ($c) => ! empty($c['href']) && $isActive($c['href'])); @endphp
                    <div>
                        <button type="button" @click="toggle('{{ $group['id'] }}')"
                            @class([
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'text-white' => $childActive,
                                'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $childActive,
                            ])>
                            <x-icon :name="$group['icon']" class="size-5 shrink-0" />
                            <span class="truncate">{{ $group['label'] }}</span>
                            <x-icon name="chevron-down" class="ms-auto size-4 shrink-0 transition-transform" x-bind:class="open['{{ $group['id'] }}'] && 'rotate-180'" />
                        </button>
                        <div x-show="open['{{ $group['id'] }}']" x-cloak class="mt-0.5 space-y-0.5 ps-4">
                            @foreach ($group['children'] as $child)
                                @if (! empty($child['header']))
                                    <p class="px-3 pb-1 pt-2.5 text-[10px] font-semibold uppercase tracking-wider text-fg-sidebar/45">{{ $child['header'] }}</p>
                                @elseif (! empty($child['soon']))
                                    <span class="flex cursor-not-allowed items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-sm text-fg-sidebar/40">
                                        <span class="truncate">{{ $child['label'] }}</span>
                                        <span class="shrink-0 rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-medium">{{ __('common.soon') }}</span>
                                    </span>
                                @else
                                    <a href="{{ $child['href'] }}" wire:navigate
                                        @class([
                                            'block rounded-lg px-3 py-1.5 text-sm transition',
                                            'bg-primary-500/15 font-medium text-white' => $isActive($child['href']),
                                            'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $isActive($child['href']),
                                        ])>
                                        {{ $child['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>
</div>
