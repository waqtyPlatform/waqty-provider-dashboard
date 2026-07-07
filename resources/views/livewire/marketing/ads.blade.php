@php
    use App\Support\Money;
    $placementLabels = [
        'banner' => __('mkt.ads.placementBanner'),
        'featured' => __('mkt.ads.placementFeatured'),
        'spotlight' => __('mkt.ads.placementSpotlight'),
    ];
    $placementColors = [
        'banner' => 'neutral',
        'featured' => 'info',
        'spotlight' => 'warning',
    ];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('mkt.ads.title')" :subtitle="__('mkt.ads.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.ads.newAd') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="ads-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->filtered) === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="megaphone" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.ads.colName') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.ads.colPlacement') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('mkt.ads.colPrice') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->filtered as $a)
                            <tr wire:key="ad-{{ $a['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $a['name'] }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$placementColors[$a['placement']] ?? 'neutral'">{{ $placementLabels[$a['placement']] ?? $a['placement'] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums font-medium text-primary-600">{{ Money::format((int) $a['price']) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-ui.toggle :on="$a['status'] === 'active'" wire:click="toggleStatus('{{ $a['id'] }}')" size="sm" />
                                        <x-ui.badge :color="$a['status'] === 'active' ? 'success' : 'neutral'">{{ $a['status'] === 'active' ? __('common.active') : __('mkt.ads.statusPaused') }}</x-ui.badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3" aria-label="{{ __('common.moreOptions') }}"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $a['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $a['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.ads.editTitle') : __('mkt.ads.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.ads.name')" wire:model="form_name" :error="$errors->first('form_name')" />
                <x-ui.select :label="__('mkt.ads.placement')" wire:model="form_placement" :options="['banner' => __('mkt.ads.placementBanner'), 'featured' => __('mkt.ads.placementFeatured'), 'spotlight' => __('mkt.ads.placementSpotlight')]" />
                <x-ui.input type="number" :label="__('mkt.lblPrice')" wire:model="form_price" min="0" step="0.01" :error="$errors->first('form_price')" />
                <x-ui.select :label="__('mkt.lblStatus')" wire:model="form_status" :options="['active' => __('common.active'), 'paused' => __('mkt.ads.statusPaused')]" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteAd" :actionLabel="__('common.delete')" />
</div>
