@php
    use App\Services\NavigationService;

    $nav = app(NavigationService::class)->build();
    $current = '/'.trim(request()->path(), '/');
    if ($current === '/'.'') { $current = '/'; }

    // Collect every nav href so the active-match can prefer the most specific link
    // (port of Sidebar.tsx isActive: exact match wins over prefix).
    $allHrefs = [];
    foreach (array_merge($nav['primary'], $nav['footer']) as $g) {
        if (! empty($g['href'])) { $allHrefs[] = $g['href']; }
        foreach ($g['children'] as $c) { if (! empty($c['href'])) { $allHrefs[] = $c['href']; } }
    }

    $isActive = function (string $href) use ($current, $allHrefs): bool {
        if ($current === $href) { return true; }
        if ($href === '/') { return false; }
        $prefix = rtrim($href, '/').'/';
        if (! str_starts_with($current, $prefix)) { return false; }
        // A more specific link matches the current path exactly -> this one is not active.
        return ! (in_array($current, $allHrefs, true) && $current !== $href);
    };

    $groupActive = function (array $g) use ($isActive): bool {
        if (! empty($g['href']) && $isActive($g['href'])) { return true; }
        foreach ($g['children'] as $c) { if (! empty($c['href']) && $isActive($c['href'])) { return true; } }
        return false;
    };

    $openGroups = [];
    foreach (array_merge($nav['primary'], $nav['footer']) as $g) {
        if ($groupActive($g)) { $openGroups[$g['id']] = true; }
    }
@endphp

<aside
    x-data="{
        open: @js($openGroups),
        toggle(id) { this.open[id] = ! this.open[id] },
    }"
    data-sidebar
    :class="$store.ui.sidebarCollapsed ? 'w-[72px]' : 'w-[260px]'"
    class="fixed inset-y-0 start-0 z-[1100] hidden shrink-0 flex-col bg-sidebar text-fg-sidebar transition-[width] duration-200 lg:flex"
>
    {{-- Brand --}}
    <div class="flex h-16 items-center gap-2.5 px-4">
        <div class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-500 text-lg font-bold text-white">و</div>
        <span x-show="!$store.ui.sidebarCollapsed" x-cloak class="text-lg font-semibold text-white">Waqty</span>
        <button
            @click="$store.ui.toggleSidebar()"
            class="ms-auto grid size-8 place-items-center rounded-lg text-fg-sidebar hover:bg-sidebar-hover hover:text-white"
            aria-label="{{ __('common.toggleSidebar') }}"
        >
            <x-icon name="menu" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-2">
        @foreach ($nav['primary'] as $group)
            @include('components.app.nav-group', ['group' => $group, 'isActive' => $isActive])
        @endforeach

        <div class="my-3 h-px bg-white/5"></div>

        @foreach ($nav['footer'] as $group)
            @include('components.app.nav-group', ['group' => $group, 'isActive' => $isActive])
        @endforeach
    </nav>
</aside>
