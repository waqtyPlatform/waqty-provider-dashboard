@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('sales.lblPackages')" :subtitle="__('sales.noPackagesDesc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('sales.lblSellPackage') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('sales.kpiTotalPackages')" :value="$this->kpis['total']" icon="shopping-bag" />
        <x-ui.kpi-card :label="__('sales.kpiActivePackages')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('sales.kpiTotalSold')" :value="$this->kpis['sold']" icon="trending-up" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('sales.kpiRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="pkg-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    @if (count($this->filtered) === 0)
        <x-ui.card><x-ui.empty-state :title="__('sales.noPackagesFound')" :description="__('sales.noPackagesDesc')" icon="shopping-bag" /></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->filtered as $p)
                <div wire:key="pkg-{{ $p['id'] }}" class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-xs {{ $p['active'] ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-fg">{{ $p['name'] }}</h3>
                            <p class="text-xs text-fg-subtle">{{ $p['sold'] }} {{ __('sales.kpiTotalSold') }}</p>
                        </div>
                        <x-ui.toggle :on="$p['active']" wire:click="toggleActive({{ $p['id'] }})" size="sm" />
                    </div>
                    <div class="my-4 flex items-baseline gap-1">
                        <span class="text-2xl font-bold text-primary-600">{{ Money::format($p['price'], false) }}</span>
                        <span class="text-sm text-fg-subtle">{{ Money::label() }}</span>
                    </div>
                    <div class="space-y-1.5 border-t border-line pt-3 text-sm">
                        <div class="flex items-center justify-between"><span class="text-fg-subtle">{{ __('sales.lblSessionsIncluded') }}</span><span class="font-medium text-fg">{{ $p['sessions'] }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-fg-subtle">{{ __('sales.lblValidity') }}</span><span class="font-medium text-fg">{{ $p['validity'] }} {{ __('sales.lblDays') }}</span></div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" class="flex-1" wire:click="openEdit({{ $p['id'] }})" icon="pencil">{{ __('common.edit') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="confirmDelete({{ $p['id'] }})" icon="trash-2" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('common.edit').' '.__('sales.packages') : __('sales.lblSellPackage')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('sales.lblPackages')" wire:model="form_name" :placeholder="__('sales.phPackageName')" :error="$errors->first('form_name')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="number" :label="__('sales.lblSessionsIncluded')" wire:model="form_sessions" min="1" :error="$errors->first('form_sessions')" />
                    <x-ui.input type="number" :label="__('sales.lblValidity')" wire:model="form_validity" min="1" :error="$errors->first('form_validity')" />
                </div>
                <x-ui.input type="number" :label="__('sales.lblRegularPrice')" wire:model="form_price" min="0" step="0.01" :error="$errors->first('form_price')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('sales.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deletePackage" :actionLabel="__('common.delete')" />
</div>
