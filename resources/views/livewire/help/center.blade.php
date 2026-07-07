<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('help.title')" :subtitle="__('help.subtitle')" />

    <div class="relative mb-6">
        <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-5 -translate-y-1/2 text-fg-subtle" />
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('help.search') }}"
            class="w-full rounded-xl border border-line bg-surface py-3 ps-11 pe-4 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
    </div>

    {{-- Contact cards --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="mailto:{{ $this->supportEmail() }}" class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3.5 transition hover:border-primary-500 hover:shadow-xs">
            <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="mail" class="size-5" /></div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-fg">{{ __('help.emailSupport') }}</p>
                <p class="truncate text-xs text-fg-subtle">{{ $this->supportEmail() }}</p>
            </div>
        </a>
        <a href="https://wa.me/{{ $this->supportWhatsapp() }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3.5 transition hover:border-primary-500 hover:shadow-xs">
            <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-success/10 text-success"><x-icon name="phone" class="size-5" /></div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-fg">{{ __('help.whatsappSupport') }}</p>
                <p class="truncate text-xs text-fg-subtle">+{{ $this->supportWhatsapp() }}</p>
            </div>
        </a>
        <a href="{{ route('help.bug-report') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3.5 transition hover:border-primary-500 hover:shadow-xs">
            <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-warning/10 text-warning"><x-icon name="alert-triangle" class="size-5" /></div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-fg">{{ __('help.bugReport') }}</p>
                <p class="truncate text-xs text-fg-subtle">{{ __('help.stillNeedHelp') }}</p>
            </div>
        </a>
    </div>

    {{-- FAQ accordion --}}
    @forelse ($this->groups as $group)
        <div class="mb-5" wire:key="grp-{{ $loop->index }}">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-fg-subtle">{{ $group['category'] }}</h2>
            <div class="divide-y divide-line overflow-hidden rounded-xl border border-line bg-surface">
                @foreach ($group['items'] as $item)
                    <div x-data="{ open: false }" wire:key="faq-{{ $loop->parent->index }}-{{ $loop->index }}">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-start">
                            <span class="text-sm font-medium text-fg">{{ $item['q'] }}</span>
                            <x-icon name="chevron-down" class="size-4 shrink-0 text-fg-subtle transition-transform" ::class="open && 'rotate-180'" />
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="px-4 pb-4 text-sm leading-relaxed text-fg-muted">{{ $item['a'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <x-ui.card>
            <x-ui.empty-state icon="search" :title="__('help.noResults')" :description="__('help.search')" />
        </x-ui.card>
    @endforelse
</div>
