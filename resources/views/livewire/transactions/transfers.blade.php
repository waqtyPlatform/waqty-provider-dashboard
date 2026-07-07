@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'transfers'" />

    <x-ui.page-header :title="__('txn.transfers.title')" :subtitle="__('txn.transfers.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('txn.transfers.new') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.transfers.kpiTransferred')" :value="Money::compact($this->kpis['transferred'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('txn.transfers.kpiCount')" :value="$this->kpis['count']" icon="receipt" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('txn.transfers.kpiPending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="trf-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.transfers.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.all') }}</option>
            <option value="completed">{{ __('txn.transfers.statusCompleted') }}</option>
            <option value="pending">{{ __('txn.transfers.statusPending') }}</option>
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.transfers.empty')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.transfers.thReference') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.transfers.thFrom') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.transfers.thTo') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.transfers.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.transfers.thDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $t)
                            <tr wire:key="trf-{{ $t['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $t['reference'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg">{{ $t['from_safe'] }}</td>
                                <td class="px-4 py-3 text-fg">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-icon name="chevron-left" class="size-3.5 shrink-0 text-fg-subtle rtl:rotate-180" />{{ $t['to_safe'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($t['amount']) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t['date'] ? Carbon::parse($t['date'])->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$t['status']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- New transfer slide-over --}}
    <x-ui.slide-over wire="showForm" :title="__('txn.transfers.newTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('txn.transfers.lblFrom')" wire:model="form_fromSafe" :options="$this->safes()" :error="$errors->first('form_fromSafe')" />
                <x-ui.select :label="__('txn.transfers.lblTo')" wire:model="form_toSafe" :placeholder="__('txn.transfers.selectSafe')" :options="$this->safes()" :error="$errors->first('form_toSafe')" />
                <x-ui.input type="number" :label="__('txn.transfers.lblAmount')" wire:model="form_amount" min="0" step="0.01" :error="$errors->first('form_amount')" />
                <x-ui.input :label="__('txn.transfers.lblNote')" wire:model="form_note" :error="$errors->first('form_note')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('txn.transfers.submit') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
