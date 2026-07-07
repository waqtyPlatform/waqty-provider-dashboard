@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $statusMeta = [
        'active' => ['label' => __('txn.packagesales.statusActive'), 'color' => 'info'],
        'completed' => ['label' => __('txn.packagesales.statusCompleted'), 'color' => 'success'],
    ];
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'package-sales'" />

    <x-ui.page-header :title="__('txn.packagesales.title')" :subtitle="__('txn.packagesales.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.packagesales.kpiActive')" :value="$this->kpis['active']" icon="shopping-bag" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('txn.packagesales.kpiSessions')" :value="$this->kpis['sessions']" icon="calendar-check" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('txn.packagesales.kpiRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="wallet" iconClass="bg-success-light text-success" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="pkg-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.packagesales.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="statusFilter" aria-label="{{ __('txn.packagesales.allStatuses') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('txn.packagesales.allStatuses') }}</option>
            @foreach ($statusMeta as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.packagesales.empty')" icon="shopping-bag" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.packagesales.thPackage') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.packagesales.thClient') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.packagesales.thSessions') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.packagesales.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.packagesales.thSoldDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            @php
                                $meta = $statusMeta[$r['status']] ?? ['label' => $r['status'], 'color' => 'neutral'];
                                $pct = $r['total'] > 0 ? min(100, (int) round($r['used'] / $r['total'] * 100)) : 0;
                            @endphp
                            <tr wire:key="pkg-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $r['package'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['client'] ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="tabular-nums text-fg-muted" dir="ltr">{{ $r['used'] }} / {{ $r['total'] }}</span>
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-surface-3">
                                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($r['amount']) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['soldAt'] ? Carbon::parse($r['soldAt'])->isoFormat('D MMM YYYY') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
