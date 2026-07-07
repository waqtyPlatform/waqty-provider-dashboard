@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('mkt.lblOffers')" :subtitle="__('marketing.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.btnNewOffer') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('mkt.lblOffers')" :value="$this->kpis['total']" icon="megaphone" />
        <x-ui.kpi-card :label="__('marketing.activeOffers')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('marketing.colUsage')" :value="$this->kpis['redemptions']" icon="trending-up" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="off-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    @if (count($this->filtered) === 0)
        <x-ui.card><x-ui.empty-state :title="__('common.noData')" :description="__('marketing.desc')" icon="megaphone" /></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->filtered as $o)
                @php $expired = Carbon::parse($o['end'])->isPast(); @endphp
                <div wire:key="off-{{ $o['id'] }}" class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-xs {{ $o['active'] && ! $expired ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-fg">{{ $o['name'] }}</h3>
                        <x-ui.toggle :on="$o['active']" wire:click="toggleActive({{ $o['id'] }})" size="sm" />
                    </div>
                    <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-primary-600">{{ $o['type'] === 'percentage' ? $o['value'].'%' : Money::format((int) $o['value'], false) }}</span>
                        <span class="text-sm font-medium text-fg-subtle">{{ $o['type'] === 'percentage' ? __('mkt.lblOFF') : Money::label() }}</span>
                    </div>
                    <div class="space-y-1.5 border-t border-line pt-3 text-sm text-fg-muted">
                        <div class="flex items-center gap-1.5"><x-icon name="calendar-days" class="size-3.5" />{{ Carbon::parse($o['start'])->isoFormat('D MMM') }} – {{ Carbon::parse($o['end'])->isoFormat('D MMM') }}</div>
                        <div class="flex items-center gap-1.5"><x-icon name="trending-up" class="size-3.5" />{{ $o['used'] }} {{ __('mkt.lblUsed') }}@if ($o['limit']) / {{ $o['limit'] }}@endif</div>
                        @if ($expired)<x-ui.badge color="error">{{ __('common.inactive') }}</x-ui.badge>@endif
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" class="flex-1" wire:click="openEdit({{ $o['id'] }})" icon="pencil">{{ __('common.edit') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="confirmDelete({{ $o['id'] }})" icon="trash-2" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.lblEditOffer') : __('mkt.lblCreateNewOffer')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.lblOfferName')" wire:model="form_name" :error="$errors->first('form_name')" />
                <x-ui.select :label="__('mkt.lblDiscountType')" wire:model.live="form_type" :options="['percentage' => __('mkt.lblPercentage'), 'fixed' => __('mkt.lblFixedAmount')]" />
                <x-ui.input type="number" :label="$form_type === 'percentage' ? __('mkt.lblPercentage') : __('mkt.lblFixedAmount')" wire:model="form_value" min="0" step="0.01" :error="$errors->first('form_value')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="date" :label="__('mkt.lblStartDate')" wire:model="form_start" :error="$errors->first('form_start')" />
                    <x-ui.input type="date" :label="__('mkt.lblEndDate')" wire:model="form_end" :error="$errors->first('form_end')" />
                </div>
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

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteOffer" :actionLabel="__('common.delete')" />
</div>
