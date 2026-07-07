@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $typeMeta = [
        'sale' => ['label' => __('txn.sales'), 'color' => 'success'],
        'refund' => ['label' => __('txn.refunds'), 'color' => 'error'],
        'advance_payment' => ['label' => __('txn.advance'), 'color' => 'info'],
        'petty_cash' => ['label' => __('txn.pettyCash'), 'color' => 'warning'],
        'transfer' => ['label' => __('txn.transfers'), 'color' => 'neutral'],
    ];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('txn.title')" :subtitle="__('txn.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.totalSales')" :value="Money::compact($this->kpis['sales'])" icon="trending-up" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('txn.totalRefunds')" :value="Money::compact($this->kpis['refunds'])" icon="rotate-ccw" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('txn.netRevenue')" :value="Money::compact($this->kpis['net'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="txn-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="typeFilter" aria-label="{{ __('txn.allTypes') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('txn.allTypes') }}</option>
            @foreach ($typeMeta as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thTxnNum') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thDateTime') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thType') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thClient') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.thMethod') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $t)
                            @php $meta = $typeMeta[$t->type] ?? ['label' => $t->type, 'color' => 'neutral']; @endphp
                            <tr wire:key="txn-{{ $t->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $t->reference_number ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t->created_at ? Carbon::parse($t->created_at)->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-fg">{{ $t->customerName() ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t->employeeName() ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t->payment_method ? __('common.method.'.strtolower($t->payment_method)) : '—' }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums {{ $t->isRefund() ? 'text-error' : 'text-fg' }}">{{ $t->isRefund() ? '−' : '' }}{{ Money::format($t->amount) }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$t->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
