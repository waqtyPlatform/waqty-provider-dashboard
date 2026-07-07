<div
    x-data="{ open: false, show() { this.open = true; $nextTick(() => $refs.input?.focus()); } }"
    @open-command-palette.window="show()"
    @keydown.window.ctrl.k.prevent="show()"
    @keydown.window.meta.k.prevent="show()"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[1400] flex items-start justify-center p-4 pt-[12vh]"
>
    <div class="absolute inset-0 bg-overlay" @click="open = false"></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="scale-95 opacity-0"
        class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl"
        @click.outside="open = false"
    >
        {{-- Search input --}}
        <div class="flex items-center gap-3 border-b border-line px-4">
            <x-icon name="search" class="size-5 shrink-0 text-fg-subtle" />
            <input
                x-ref="input"
                wire:model.live.debounce.150ms="search"
                @keydown.enter.prevent="$root.querySelector('[data-cmd-first]')?.click()"
                type="text"
                placeholder="{{ __('search.placeholder') }}"
                class="w-full bg-transparent py-4 text-sm text-fg placeholder:text-fg-subtle focus:outline-none"
            >
            <kbd class="hidden rounded border border-line bg-surface-2 px-1.5 py-0.5 font-mono text-[10px] text-fg-subtle sm:block">Esc</kbd>
        </div>

        {{-- Results --}}
        <div class="max-h-[50vh] overflow-y-auto p-2">
            @forelse ($this->results as $item)
                <a
                    href="{{ $item['href'] }}"
                    wire:navigate
                    @click="open = false"
                    @if ($loop->first) data-cmd-first @endif
                    wire:key="cmd-{{ $item['href'] }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-fg hover:bg-surface-2 focus:bg-surface-2 focus:outline-none"
                >
                    @if ($item['icon'])
                        <span class="grid size-7 shrink-0 place-items-center rounded-md bg-surface-2 text-fg-muted"><x-icon :name="$item['icon']" class="size-4" /></span>
                    @endif
                    <span class="flex-1 truncate">{{ $item['label'] }}</span>
                    <span class="shrink-0 text-xs text-fg-subtle">{{ $item['group'] }}</span>
                </a>
            @empty
                <p class="px-3 py-8 text-center text-sm text-fg-subtle">{{ __('common.noData') }}</p>
            @endforelse
        </div>
    </div>
</div>
