@php $active = $isActive; @endphp

@if (empty($group['children']))
    {{-- Standalone link (e.g. Dashboard) --}}
    <a
        href="{{ $group['href'] }}"
        wire:navigate
        @class([
            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-primary-500 text-white' => $active($group['href']),
            'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $active($group['href']),
        ])
        :title="$store.ui.sidebarCollapsed ? @js($group['label']) : null"
    >
        <x-icon :name="$group['icon']" class="size-5 shrink-0" />
        <span x-show="!$store.ui.sidebarCollapsed" x-cloak class="truncate">{{ $group['label'] }}</span>
    </a>
@else
    @php $childActive = collect($group['children'])->contains(fn ($c) => ! empty($c['href']) && $active($c['href'])); @endphp
    <div>
        <button
            type="button"
            @click="$store.ui.sidebarCollapsed ? ($store.ui.toggleSidebar(), open['{{ $group['id'] }}'] = true) : toggle('{{ $group['id'] }}')"
            @class([
                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                'text-white' => $childActive,
                'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $childActive,
            ])
            :title="$store.ui.sidebarCollapsed ? @js($group['label']) : null"
        >
            <x-icon :name="$group['icon']" class="size-5 shrink-0" />
            <span x-show="!$store.ui.sidebarCollapsed" x-cloak class="truncate">{{ $group['label'] }}</span>
            <x-icon
                x-show="!$store.ui.sidebarCollapsed" x-cloak
                name="chevron-down"
                class="ms-auto size-4 shrink-0 transition-transform"
                x-bind:class="open['{{ $group['id'] }}'] && 'rotate-180'"
            />
        </button>

        <div x-show="open['{{ $group['id'] }}'] && !$store.ui.sidebarCollapsed" x-cloak class="mt-0.5 space-y-0.5 ps-4">
            @foreach ($group['children'] as $child)
                @if (! empty($child['header']))
                    <p class="px-3 pb-1 pt-2.5 text-[10px] font-semibold uppercase tracking-wider text-fg-sidebar/45">{{ $child['header'] }}</p>
                @elseif (! empty($child['soon']))
                    <span class="flex cursor-not-allowed items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-sm text-fg-sidebar/40">
                        <span class="truncate">{{ $child['label'] }}</span>
                        <span class="shrink-0 rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-medium">{{ __('common.soon') }}</span>
                    </span>
                @else
                    <a
                        href="{{ $child['href'] }}"
                        wire:navigate
                        @class([
                            'block rounded-lg px-3 py-1.5 text-sm transition',
                            'bg-primary-500/15 font-medium text-white' => $active($child['href']),
                            'text-fg-sidebar hover:bg-sidebar-hover hover:text-white' => ! $active($child['href']),
                        ])
                    >
                        {{ $child['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
