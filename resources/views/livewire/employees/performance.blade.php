<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.performance.title')" :subtitle="__('emp.performance.subtitle')">
        <x-slot:actions>
            <select wire:model.live="period" aria-label="{{ __('emp.performance.period') }}"
                class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="month">{{ __('emp.performance.periodMonth') }}</option>
                <option value="quarter">{{ __('emp.performance.periodQuarter') }}</option>
                <option value="year">{{ __('emp.performance.periodYear') }}</option>
            </select>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.performance.kpiTopPerformer')" :value="$this->kpis['topPerformer']" icon="trending-up" iconClass="bg-primary-50 text-primary-600" />
        <x-ui.kpi-card :label="__('emp.performance.kpiAvgRating')" :value="number_format($this->kpis['avgRating'], 1)" icon="star" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.performance.kpiTotalRevenue')" :value="\App\Support\Money::compact($this->kpis['totalRevenue'])" icon="wallet" iconClass="bg-success-light text-success" />
    </div>

    {{-- Ranking table --}}
    <x-ui.card padding="p-0">
        @if (count($this->ranking) === 0)
            <x-ui.empty-state :title="__('emp.performance.emptyTitle')" :description="__('emp.performance.emptyDesc')" icon="bar-chart-3" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.performance.colRank') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.performance.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.performance.colBookings') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.performance.colRevenue') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.performance.colRating') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.performance.colUtilization') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->ranking as $row)
                            @php
                                $rankStyles = [
                                    1 => 'bg-warning-light text-warning ring-1 ring-warning/30',
                                    2 => 'bg-info-light text-info ring-1 ring-info/30',
                                    3 => 'bg-primary-50 text-primary-600 ring-1 ring-primary-500/30',
                                ];
                                $rankCls = $rankStyles[$row['rank']] ?? 'bg-surface-3 text-fg-muted';
                                $stars = (int) round($row['rating']);
                                $u = $row['utilization'];
                                $uBar = $u >= 80 ? 'bg-success' : ($u >= 50 ? 'bg-primary-500' : 'bg-warning');
                            @endphp
                            <tr wire:key="perf-{{ $row['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <span class="grid size-8 place-items-center rounded-full text-sm font-bold tabular-nums {{ $rankCls }}">{{ $row['rank'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$row['employee']" />
                                        <span class="font-medium text-fg">{{ $row['employee'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-fg-muted">
                                    {{ number_format($row['bookings']) }} <span class="text-xs text-fg-subtle">{{ __('emp.performance.bookingsUnit') }}</span>
                                </td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ \App\Support\Money::format($row['revenue']) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex items-center gap-0.5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <x-icon name="star" class="size-3.5 {{ $i <= $stars ? 'fill-current text-warning' : 'text-fg-subtle' }}" />
                                            @endfor
                                        </div>
                                        <span class="text-xs font-medium tabular-nums text-fg-muted">{{ number_format($row['rating'], 1) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="w-40">
                                        <div class="mb-1 flex items-center justify-between">
                                            <span class="text-xs font-semibold tabular-nums {{ $u >= 80 ? 'text-success' : ($u >= 50 ? 'text-fg' : 'text-warning') }}">{{ $u }}%</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-3">
                                            <div class="h-full rounded-full {{ $uBar }}" style="width: {{ min(100, max(0, $u)) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
