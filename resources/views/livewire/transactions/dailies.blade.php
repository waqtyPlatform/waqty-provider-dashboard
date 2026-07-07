@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'dailies'" />

    <x-ui.page-header :title="__('txn.dailies.title')" :subtitle="__('txn.dailies.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.dailies.totalSales')" :value="Money::compact($this->kpis['sales'])" icon="trending-up" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('txn.dailies.totalNet')" :value="Money::compact($this->kpis['net'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card
            :label="__('txn.dailies.bestDay')"
            :value="$this->kpis['bestDate'] ? Carbon::parse($this->kpis['bestDate'])->isoFormat('D MMM YYYY') : '—'"
            :trend="$this->kpis['bestDate'] ? Money::compact($this->kpis['bestNet']) : null"
            icon="star" iconClass="bg-warning-light text-warning" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4 text-xs text-fg-muted">
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 shrink-0 rounded-full bg-success"></span>{{ __('txn.dailies.sales') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 shrink-0 rounded-full bg-warning"></span>{{ __('txn.dailies.refunds') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 shrink-0 rounded-full bg-error"></span>{{ __('txn.dailies.expenses') }}</span>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="bar-chart-3" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.dailies.thDate') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.dailies.thSales') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.dailies.thRefunds') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.dailies.thExpenses') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.dailies.thNet') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.dailies.thBreakdown') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $row)
                            @php
                                $barTotal = max(1, $row['sales'] + $row['refunds'] + $row['expenses']);
                                $salesPct = round($row['sales'] / $barTotal * 100, 2);
                                $refundsPct = round($row['refunds'] / $barTotal * 100, 2);
                                $expensesPct = round($row['expenses'] / $barTotal * 100, 2);
                            @endphp
                            <tr wire:key="daily-{{ $row['date'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $row['date'] ? Carbon::parse($row['date'])->isoFormat('ddd، D MMM') : '—' }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($row['sales']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($row['refunds']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($row['expenses']) }}</td>
                                <td class="px-4 py-3 text-end font-bold tabular-nums {{ $row['net'] >= 0 ? 'text-success' : 'text-error' }}">{{ $row['net'] < 0 ? '−' : '' }}{{ Money::format(abs($row['net'])) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex h-2 w-full min-w-[120px] overflow-hidden rounded-full bg-surface-3" role="presentation" title="{{ __('txn.dailies.thBreakdown') }}">
                                        <div class="bg-success" style="width: {{ $salesPct }}%"></div>
                                        <div class="bg-warning" style="width: {{ $refundsPct }}%"></div>
                                        <div class="bg-error" style="width: {{ $expensesPct }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
