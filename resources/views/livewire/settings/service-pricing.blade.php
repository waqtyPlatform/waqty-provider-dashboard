<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.servicePricing.title')" :subtitle="__('settings.servicePricing.desc')">
        <x-slot:actions>
            <x-ui.button icon="check" wire:click="save">{{ __('common.save') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Scope segmented control --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="inline-flex rounded-lg border border-line bg-surface-2 p-1">
            @foreach (\App\Livewire\Settings\ServicePricing::SCOPES as $s)
                <button type="button" wire:click="$set('scope', '{{ $s }}')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-surface text-fg shadow-xs' => $scope === $s,
                        'text-fg-muted hover:text-fg' => $scope !== $s,
                    ])>{{ __('settings.servicePricing.scope'.ucfirst($s)) }}</button>
            @endforeach
        </div>

        @if ($scope !== 'base')
            <select wire:model.live="scopeId"
                class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">{{ __('settings.servicePricing.selectTarget') }}</option>
                @foreach ($this->targetOptions() as $uuid => $name)
                    <option value="{{ $uuid }}">{{ $name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if ($scope !== 'base' && ! $scopeId)
        <x-ui.card>
            <x-ui.empty-state icon="wallet" :title="__('settings.servicePricing.pickTarget')" :description="__('settings.servicePricing.desc')" />
        </x-ui.card>
    @else
        <x-ui.card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('settings.servicePricing.colService') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('settings.servicePricing.colBase') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ $scope === 'base' ? __('settings.servicePricing.colBase') : __('settings.servicePricing.colOverride') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $s)
                            @php $base = $this->basePrice($s['uuid']); @endphp
                            <tr wire:key="price-{{ $s['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $s['name'] }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ $base !== null ? \App\Support\Money::format($base) : '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <input type="number" min="0" step="0.01" wire:model="edits.{{ $s['uuid'] }}"
                                            placeholder="{{ $base !== null ? \App\Support\Money::fromMinor($base) : '0' }}"
                                            class="w-28 rounded-lg border border-line bg-surface px-3 py-1.5 text-end text-sm tabular-nums text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                        <span class="text-xs text-fg-subtle">{{ \App\Support\Money::label() }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
        <p class="mt-3 text-xs text-fg-subtle">{{ __('settings.servicePricing.hint') }}</p>
    @endif
</div>
