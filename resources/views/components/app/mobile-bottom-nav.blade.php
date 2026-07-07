@php
    $term = $provider->terminology();
    $items = [
        ['label' => __('sidebar.dashboard'), 'icon' => 'layout-dashboard', 'href' => '/', 'active' => request()->is('/')],
        ['label' => __('sidebar.bookings'), 'icon' => 'calendar-days', 'href' => '/bookings', 'active' => request()->is('bookings*')],
        ['label' => $term['customer'], 'icon' => 'users', 'href' => '/customers', 'active' => request()->is('customers*')],
        ['label' => __('sidebar.sales'), 'icon' => 'shopping-bag', 'href' => '/sales', 'active' => request()->is('sales*')],
    ];
@endphp

<nav x-data class="fixed inset-x-0 bottom-0 z-[1150] flex items-stretch border-t border-line bg-surface/95 backdrop-blur lg:hidden" aria-label="{{ __('common.menu') }}">
    @foreach ($items as $item)
        <a href="{{ $item['href'] }}" wire:navigate
           class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ $item['active'] ? 'text-primary-600' : 'text-fg-subtle' }}">
            <x-icon :name="$item['icon']" class="size-5" />
            <span class="truncate">{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button @click="$store.ui.mobileNavOpen = true" aria-label="{{ __('common.menu') }}"
            class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium text-fg-subtle">
        <x-icon name="menu" class="size-5" />
        <span>{{ __('common.menu') }}</span>
    </button>
</nav>
