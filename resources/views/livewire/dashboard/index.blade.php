@php
    use App\Support\Money;
    $s = $this->summary;
    $c = $this->counts;
    $term = $provider->terminology();
    $isClinic = $term['requiresIntake'];
    $fmtTrend = fn ($v) => ($v >= 0 ? '+' : '').number_format($v, 1).'%';
@endphp

<div class="p-4 sm:p-6">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-primary-600">{{ $term['label'] }}</p>
            <h1 class="mt-0.5 text-2xl font-semibold text-fg">{{ __('dash.welcome') }}{{ $provider->name() ? ', '.$provider->name() : '' }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-lg border border-line bg-surface p-0.5">
                @foreach (['7d' => '7D', '30d' => '30D', '90d' => '90D'] as $key => $lbl)
                    <button
                        wire:click="$set('range', '{{ $key }}')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $key ? 'bg-primary-500 text-white' : 'text-fg-muted hover:text-fg' }}"
                    >{{ $lbl }}</button>
                @endforeach
            </div>
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPI strip --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        <x-ui.kpi-card :label="__('dash.kpiRev')" :value="Money::compact($s->total_revenue)" icon="wallet" :trend="$fmtTrend($s->revenue_trend)" :trendUp="$s->revenue_trend >= 0" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="$isClinic ? __('dash.kpiAppointments') : __('dash.kpiBookings')" :value="number_format($s->total_bookings)" icon="calendar-check" :trend="$fmtTrend($s->bookings_trend)" :trendUp="$s->bookings_trend >= 0" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="$isClinic ? __('dash.kpiNewPatients') : __('dash.kpiNewClients')" :value="number_format($s->new_clients)" icon="user-plus" :trend="$fmtTrend($s->clients_trend)" :trendUp="$s->clients_trend >= 0" iconClass="bg-purple-100 text-purple-600" />
        <x-ui.kpi-card :label="__('dash.kpiInvoices')" :value="number_format($s->total_invoices)" icon="receipt" iconClass="bg-surface-3 text-fg-muted" />
        <x-ui.kpi-card :label="__('dash.kpiReturns')" :value="number_format($s->total_returns)" icon="rotate-ccw" iconClass="bg-error-light text-error" />
    </div>

    {{-- Charts row --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-fg">{{ __('dash.revenueOverview') }}</h2>
                <span class="text-sm text-fg-muted">{{ Money::format($s->total_revenue) }}</span>
            </div>
            <div wire:ignore>
                <div x-data="chart(@js($this->revenueOptions()))" @dash-charts.window="update($event.detail.revenue)"></div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-2 font-semibold text-fg">{{ __('dash.bookingStatus') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->donutOptions()))" @dash-charts.window="update($event.detail.donut)"></div>
            </div>
        </x-ui.card>
    </div>

    {{-- Secondary stats --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.card class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-info-light text-info"><x-icon name="calendar-days" class="size-5" /></span>
            <div><p class="text-sm text-fg-muted">{{ __('dash.dateToday') }}</p><p class="text-lg font-semibold text-fg">{{ $c->todayTotal() }}</p></div>
        </x-ui.card>
        <x-ui.card class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-success-light text-success"><x-icon name="user-cog" class="size-5" /></span>
            <div><p class="text-sm text-fg-muted">{{ $term['staff'] }}</p><p class="text-lg font-semibold text-fg">{{ $c->employeesActive() }}/{{ $c->employeesTotal() }}</p></div>
        </x-ui.card>
        <x-ui.card class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-warning-light text-warning"><x-icon name="star" class="size-5" /></span>
            <div><p class="text-sm text-fg-muted">{{ __('employees.rating') }}</p><p class="text-lg font-semibold text-fg">{{ number_format($c->ratingsAverage(), 1) }} <span class="text-xs font-normal text-fg-subtle">({{ $c->ratingsTotal() }})</span></p></div>
        </x-ui.card>
        <x-ui.card class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-primary-100 text-primary-600"><x-icon name="gauge" class="size-5" /></span>
            <div><p class="text-sm text-fg-muted">{{ __('dash.occupancy') }}</p><p class="text-lg font-semibold text-fg">{{ number_format($s->occupancy_rate, 1) }}%</p></div>
        </x-ui.card>
    </div>

    {{-- Next appointment + top lists --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Next appointment --}}
        <x-ui.card>
            <h2 class="mb-3 font-semibold text-fg">{{ __('dash.upcomingAppointments') }}</h2>
            @if ($next = $this->nextUpcoming)
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:bg-primary-900/20">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="data_get($next, 'user.name', '—')" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-fg">{{ data_get($next, 'user.name', '—') }}</p>
                            <p class="truncate text-sm text-fg-muted">{{ data_get($next, 'service.name', '—') }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-primary-200/50 pt-3 text-sm">
                        <span class="flex items-center gap-1.5 text-fg-muted"><x-icon name="clock" class="size-4" />{{ data_get($next, 'start_time', '—') }}</span>
                        <span class="font-semibold text-primary-600">{{ Money::format((int) data_get($next, 'price', 0)) }}</span>
                    </div>
                    @if ($emp = data_get($next, 'employee.name'))
                        <p class="mt-2 text-xs text-fg-subtle">{{ $term['staff'] }}: {{ $emp }}</p>
                    @endif
                </div>
            @else
                <x-ui.empty-state :title="__('common.noData')" icon="calendar-check" />
            @endif
        </x-ui.card>

        {{-- Top services --}}
        <x-ui.card>
            <h2 class="mb-3 font-semibold text-fg">{{ __('dash.topServices') }}</h2>
            <div class="space-y-2.5">
                @forelse ($s->top_services as $i => $svc)
                    <div class="flex items-center gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-surface-2 text-xs font-semibold text-fg-muted">{{ $i + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm text-fg">{{ data_get($svc, 'name') }}</span>
                        <span class="text-xs text-fg-subtle">{{ data_get($svc, 'count') }}×</span>
                        <span class="text-sm font-medium tabular-nums text-primary-600">{{ Money::compact((int) data_get($svc, 'revenue', 0)) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-fg-subtle">{{ __('common.noData') }}</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Top clients --}}
        <x-ui.card>
            <h2 class="mb-3 font-semibold text-fg">{{ __('dash.topClients') }}</h2>
            <div class="space-y-2.5">
                @forelse ($s->top_clients as $i => $cl)
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="data_get($cl, 'name', '—')" class="size-7 text-xs" />
                        <span class="min-w-0 flex-1 truncate text-sm text-fg">{{ data_get($cl, 'name') }}</span>
                        <span class="text-xs text-fg-subtle">{{ data_get($cl, 'visits') }} {{ __('dash.colVisits') }}</span>
                        <span class="text-sm font-medium tabular-nums text-primary-600">{{ Money::compact((int) data_get($cl, 'spent', 0)) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-fg-subtle">{{ __('common.noData') }}</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
