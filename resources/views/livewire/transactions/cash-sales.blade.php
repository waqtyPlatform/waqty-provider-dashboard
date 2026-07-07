@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'cash-sales'" />

    <x-ui.page-header :title="__('txn.cash.title')" :subtitle="__('txn.cash.desc')">
        <x-slot:actions>
            <x-ui.button variant="secondary" size="md" icon="receipt" wire:click="exportCsv" loadingTarget="exportCsv">
                {{ __('txn.cash.export') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.cash.total')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('txn.transactions')" :value="$this->kpis['count']" icon="receipt" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('txn.cash.avgTicket')" :value="Money::format($this->kpis['average'])" icon="trending-up" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="cashsales-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.cash.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.cash.emptyTitle')" :description="__('txn.cash.emptyDesc')" icon="receipt" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.cash.thReceipt') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thDateTime') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thClient') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.cash.thServices') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thMethod') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.thAmount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $row)
                            <tr wire:key="cash-{{ $loop->index }}-{{ $row['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $row['receipt'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $row['created_at'] ? Carbon::parse($row['created_at'])->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                <td class="px-4 py-3 font-medium text-fg">{{ $row['client'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $row['services'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $row['employee'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $row['method'] ? __('common.method.'.strtolower($row['method'])) : '—' }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($row['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
