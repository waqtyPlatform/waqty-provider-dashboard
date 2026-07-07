@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'advance-payments'" />

    <x-ui.page-header :title="__('txn.advancepayments.title')" :subtitle="__('txn.advancepayments.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('txn.advancepayments.new') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.advancepayments.kpiTotal')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('txn.advancepayments.kpiOutstanding')" :value="Money::compact($this->kpis['outstanding'])" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('txn.advancepayments.kpiCount')" :value="$this->kpis['count']" icon="receipt" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="adv-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.advancepayments.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.advancepayments.emptyTitle')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.advancepayments.thReference') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.advancepayments.thClient') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.advancepayments.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.advancepayments.thDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.advancepayments.thAppliedTo') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            <tr wire:key="adv-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $r['reference'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg">{{ $r['client'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format((int) ($r['amount'] ?? 0)) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ !empty($r['date']) ? Carbon::parse($r['date'])->isoFormat('D MMM') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$r['status']" /></td>
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $r['applied_to'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- New advance slide-over --}}
    <x-ui.slide-over wire="showForm" :title="__('txn.advancepayments.newTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('txn.advancepayments.lblClient')" wire:model="form_client" :error="$errors->first('form_client')" required />
                <x-ui.input type="number" :label="__('txn.advancepayments.lblAmount')" wire:model="form_amount" min="0" step="0.01" :error="$errors->first('form_amount')" required />
                <x-ui.select :label="__('txn.advancepayments.lblMethod')" wire:model="form_method" :options="['cash' => __('txn.advancepayments.methodCash'), 'card' => __('txn.advancepayments.methodCard'), 'bank' => __('txn.advancepayments.methodBank')]" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
