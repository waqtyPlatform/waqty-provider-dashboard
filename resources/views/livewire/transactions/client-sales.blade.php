@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $groupMeta = [
        'vip' => ['label' => __('txn.clientsales.groupVip'), 'color' => 'primary'],
        'regular' => ['label' => __('txn.clientsales.groupRegular'), 'color' => 'neutral'],
        'new' => ['label' => __('txn.clientsales.groupNew'), 'color' => 'info'],
    ];
    $max = $this->maxSpent;
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'client-sales'" />

    <x-ui.page-header :title="__('txn.clientsales.title')" :subtitle="__('txn.clientsales.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.clientsales.kpiRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="wallet" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('txn.clientsales.kpiClients')" :value="$this->kpis['clients']" icon="users" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('txn.clientsales.kpiTopSpender')" :value="$this->kpis['top'] ?: '—'" icon="star" iconClass="bg-warning-light text-warning" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="clientsales-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.clientsales.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.clientsales.thClient') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.clientsales.thGroup') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.clientsales.thVisits') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.clientsales.thTotal') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.clientsales.thLastPurchase') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $row)
                            @php
                                $meta = $groupMeta[$row['group']] ?? ['label' => $row['group'], 'color' => 'neutral'];
                                $parts = preg_split('/\s+/', trim((string) $row['name'])) ?: [];
                                $initials = mb_substr($parts[0] ?? '', 0, 1).(isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
                                $pct = $max > 0 ? max(4, (int) round($row['total'] / $max * 100)) : 0;
                            @endphp
                            <tr wire:key="cs-{{ $loop->index }}-{{ $row['name'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">{{ $initials }}</span>
                                        <span class="font-medium text-fg">{{ $row['name'] ?: '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 tabular-nums text-fg-muted">{{ $row['visits'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-end gap-1.5">
                                        <span class="font-medium tabular-nums text-fg">{{ Money::format($row['total']) }}</span>
                                        <div class="h-1 w-24 overflow-hidden rounded-full bg-surface-3">
                                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">{{ $row['last_purchase'] ? Carbon::parse($row['last_purchase'])->isoFormat('D MMM YYYY') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
