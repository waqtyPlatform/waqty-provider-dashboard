<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.integrations.title')" :subtitle="__('settings.integrations.desc')" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($this->items as $item)
            <div wire:key="integration-{{ $item['id'] }}" class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-xs">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-fg">{{ $item['name'] }}</h3>
                        <x-ui.badge :color="$item['connected'] ? 'success' : 'neutral'" class="mt-1.5">
                            {{ $item['connected'] ? __('settings.integrations.connected') : __('settings.integrations.disconnected') }}
                        </x-ui.badge>
                    </div>
                    <x-ui.toggle :on="$item['connected']" wire:click="toggle({{ $item['id'] }})" size="sm" />
                </div>
                <p class="mt-3 text-sm text-fg-muted">{{ $item['description'] }}</p>
            </div>
        @endforeach
    </div>
</div>
