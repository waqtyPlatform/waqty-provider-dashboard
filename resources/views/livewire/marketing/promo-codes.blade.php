@php use App\Support\Money; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('mkt.lblPromoCodes')" :subtitle="__('marketing.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('marketing.newPromo') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('mkt.lblPromoCodes')" :value="$this->kpis['total']" icon="megaphone" />
        <x-ui.kpi-card :label="__('marketing.activeCodes')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('marketing.colUsage')" :value="$this->kpis['redemptions']" icon="trending-up" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="promo-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->filtered) === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="megaphone" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblCode') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblDiscountType') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('marketing.colUsage') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->filtered as $c)
                            <tr wire:key="promo-{{ $c['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                                        <code class="rounded bg-surface-2 px-2 py-1 font-mono text-xs font-semibold text-fg" dir="ltr">{{ $c['code'] }}</code>
                                        <button type="button" @click="navigator.clipboard.writeText('{{ $c['code'] }}'); copied = true; setTimeout(() => copied = false, 1500)" class="text-fg-subtle hover:text-primary-600" :title="'{{ __('mkt.lblCopyCode') }}'">
                                            <x-icon name="check" class="size-4" x-show="copied" x-cloak />
                                            <x-icon name="image" class="size-4" x-show="!copied" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-primary-600">{{ $c['type'] === 'percentage' ? $c['value'].'% '.__('mkt.lblOFF') : Money::format((int) $c['value']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ $c['used'] }}@if ($c['limit']) / {{ $c['limit'] }}@endif</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-ui.toggle :on="$c['active']" wire:click="toggleActive({{ $c['id'] }})" size="sm" />
                                        <span class="text-xs {{ $c['active'] ? 'text-success' : 'text-fg-subtle' }}">{{ $c['active'] ? __('common.active') : __('common.inactive') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit({{ $c['id'] }})" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete({{ $c['id'] }})" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
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
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('marketing.editCode') : __('marketing.newPromo')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.lblCode')" wire:model="form_code" dir="ltr" class="font-mono uppercase" :error="$errors->first('form_code')" hint="A-Z 0-9, 3–20" />
                <x-ui.select :label="__('mkt.lblDiscountType')" wire:model.live="form_type" :options="['percentage' => __('mkt.lblPercentage'), 'fixed' => __('mkt.lblFixedAmount')]" />
                <x-ui.input type="number" :label="$form_type === 'percentage' ? __('mkt.lblPercentage') : __('mkt.lblFixedAmount')" wire:model="form_value" min="0" step="0.01" :error="$errors->first('form_value')" />
                <x-ui.input type="number" :label="__('mkt.lblUsageLimit')" wire:model="form_limit" min="0" :error="$errors->first('form_limit')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('mkt.lblActive') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteCode" :actionLabel="__('common.delete')" />
</div>
