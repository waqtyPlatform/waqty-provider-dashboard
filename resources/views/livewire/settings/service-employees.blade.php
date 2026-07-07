<div class="mx-auto max-w-5xl p-6">
    <x-ui.page-header :title="__('settings.serviceEmployees.title')" :subtitle="__('settings.serviceEmployees.desc')">
        <x-slot:actions>
            <x-ui.button icon="check" wire:click="saveAll">{{ __('settings.serviceEmployees.saveChanges') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-4 max-w-sm">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-fg-subtle" />
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('settings.serviceEmployees.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 ps-9 pe-3 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
        </div>
    </div>

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-2">
                        <th class="sticky start-0 z-10 bg-surface-2 px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">{{ __('settings.serviceEmployees.serviceName') }}</th>
                        @foreach ($employees as $e)
                            <th class="px-3 py-3 text-center text-xs font-semibold text-fg" wire:key="emp-head-{{ $e['uuid'] }}">
                                <div class="mx-auto flex w-16 flex-col items-center gap-1">
                                    <x-ui.avatar :name="$e['name']" size="size-8" />
                                    <span class="truncate text-[11px] font-medium text-fg-muted" title="{{ $e['name'] }}">{{ \Illuminate\Support\Str::of($e['name'])->explode(' ')->first() }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->filteredServices() as $s)
                        <tr wire:key="row-{{ $s['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="sticky start-0 z-10 bg-surface px-4 py-3">
                                <button type="button" wire:click="toggleRow('{{ $s['uuid'] }}')" class="text-start font-medium text-fg hover:text-primary-600">{{ $s['name'] }}</button>
                            </td>
                            @foreach ($employees as $e)
                                <td class="px-3 py-3 text-center" wire:key="cell-{{ $s['uuid'] }}-{{ $e['uuid'] }}">
                                    <input type="checkbox" wire:model="assignments.{{ $s['uuid'] }}.{{ $e['uuid'] }}"
                                        class="size-4 rounded border-line text-primary-600 focus:ring-primary-500/30" />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <p class="mt-3 text-xs text-fg-subtle">{{ __('settings.serviceEmployees.matrix') }}</p>
</div>
