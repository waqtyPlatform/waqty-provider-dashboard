@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('mkt.packages.title')" :subtitle="__('mkt.packages.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.btnNewPackage') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('mkt.packages.kpiTotal')" :value="$this->kpis['total']" icon="shopping-bag" />
        <x-ui.kpi-card :label="__('mkt.packages.kpiActive')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('mkt.lblTotalSold')" :value="$this->kpis['sold']" icon="trending-up" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="pkg-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    @if (count($this->filtered) === 0)
        <x-ui.card><x-ui.empty-state :title="__('mkt.lblNoPackages')" :description="__('mkt.msgNoPackagesDesc')" icon="shopping-bag" /></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->filtered as $p)
                @php $savings = (int) $p['original'] - (int) $p['price']; @endphp
                <div wire:key="pkg-{{ $p['id'] }}" class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-xs {{ $p['active'] ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-fg">{{ $p['name'] }}</h3>
                        <x-ui.toggle :on="$p['active']" wire:click="toggleActive({{ $p['id'] }})" size="sm" />
                    </div>

                    <div class="my-3 flex flex-wrap items-baseline gap-2">
                        <span class="text-2xl font-bold text-primary-600">{{ Money::format((int) $p['price']) }}</span>
                        @if ($savings > 0)
                            <span class="text-sm text-fg-subtle line-through">{{ Money::format((int) $p['original'], false) }}</span>
                            <x-ui.badge color="success">{{ __('mkt.packages.save') }} {{ Money::format($savings, false) }}</x-ui.badge>
                        @endif
                    </div>

                    <div class="mb-3">
                        <p class="mb-1.5 text-xs font-medium text-fg-subtle">{{ __('mkt.lblIncludedServices') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($p['services'] as $slug)
                                <span class="inline-flex items-center rounded-md bg-surface-2 px-2 py-0.5 text-xs text-fg-muted">{{ $this->serviceName($slug) }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-1.5 border-t border-line pt-3 text-sm text-fg-muted">
                        <div class="flex items-center gap-1.5"><x-icon name="calendar-check" class="size-3.5" />{{ $p['sessions'] }} {{ __('mkt.packages.sessions') }}</div>
                        <div class="flex items-center gap-1.5"><x-icon name="shopping-bag" class="size-3.5" />{{ $p['sold'] }} {{ __('mkt.lblSold') }}</div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" class="flex-1" wire:click="openEdit({{ $p['id'] }})" icon="pencil">{{ __('common.edit') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="confirmDelete({{ $p['id'] }})" icon="trash-2" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.lblEditPackage') : __('mkt.lblCreateNewPackage')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.lblPackageName')" wire:model="form_name" :placeholder="__('mkt.phPackageName')" required :error="$errors->first('form_name')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="number" :label="__('mkt.lblPrice')" wire:model="form_price" min="0" step="0.01" required :error="$errors->first('form_price')" />
                    <x-ui.input type="number" :label="__('mkt.lblOriginalPrice')" wire:model="form_original" min="0" step="0.01" :error="$errors->first('form_original')" />
                </div>
                <x-ui.input type="number" :label="__('mkt.packages.lblSessions')" wire:model="form_sessions" min="1" required :error="$errors->first('form_sessions')" />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('mkt.lblAssignServices') }}<span class="text-error"> *</span></label>
                    <div class="max-h-52 space-y-1 overflow-y-auto rounded-lg border border-line p-2">
                        @foreach ($this->serviceOptions as $slug => $name)
                            <label wire:key="opt-{{ $slug }}" class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-surface-2">
                                <input type="checkbox" wire:model="form_services" value="{{ $slug }}" class="size-4 rounded border-line text-primary-500 focus:ring-primary-500/30">
                                <span class="text-sm text-fg">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form_services')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('mkt.lblActive') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('mkt.btnSavePackage') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('mkt.lblDeletePackage')" :description="__('mkt.msgDeletePackageConfirm')" action="deletePackage" :actionLabel="__('common.delete')" />
</div>
