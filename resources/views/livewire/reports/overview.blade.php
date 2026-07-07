@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-fg">{{ __('reports.title') }}</h1>
            <p class="mt-0.5 text-sm text-fg-muted">{{ __('reports.subtitle') }}</p>
        </div>
        <div class="inline-flex rounded-lg border border-line bg-surface p-0.5">
            @foreach (['30d' => __('reports.last30Days'), '3m' => __('reports.last3Months'), '6m' => __('reports.last6Months')] as $key => $lbl)
                <button wire:click="$set('range', '{{ $key }}')" class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $key ? 'bg-primary-500 text-white' : 'text-fg-muted hover:text-fg' }}">{{ $lbl }}</button>
            @endforeach
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('reports.kpiRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('reports.kpiBookings')" :value="number_format($this->kpis['bookings'])" icon="calendar-check" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('reports.kpiActiveClients')" :value="number_format($this->kpis['clients'])" icon="users" iconClass="bg-purple-100 text-purple-600" />
        <x-ui.kpi-card :label="__('reports.kpiGrowth')" :value="($this->kpis['growth'] >= 0 ? '+' : '').number_format($this->kpis['growth'], 1).'%'" icon="trending-up" :trendUp="$this->kpis['growth'] >= 0" iconClass="bg-success-light text-success" />
    </div>

    <x-ui.card class="mb-6">
        <h2 class="mb-2 font-semibold text-fg">{{ __('reports.chartRevVsExp') }}</h2>
        <div wire:ignore>
            <div x-data="chart(@js($this->revenueLineOptions()))" @reports-charts.window="update($event.detail.line)"></div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-2 font-semibold text-fg">{{ __('reports.revenue') }} — {{ __('reports.allBranches') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->branchBarOptions()))" @reports-charts.window="update($event.detail.branch)"></div>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-2 font-semibold text-fg">{{ __('reports.revenue') }} — {{ __('reports.tabEmployees') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->employeeBarOptions()))" @reports-charts.window="update($event.detail.employee)"></div>
            </div>
        </x-ui.card>
    </div>
</div>
