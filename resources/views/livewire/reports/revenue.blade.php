@php use App\Support\Money; @endphp

<div class="p-6">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-fg">{{ __('reports.tabRevenue') }}</h1>
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

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('reports.kpiRevenue')" :value="Money::format($this->revenue->total_revenue)" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('employees.branch')" :value="count($this->revenue->by_branch)" icon="building-2" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('reports.tabEmployees')" :value="count($this->revenue->by_employee)" icon="user-cog" iconClass="bg-success-light text-success" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="mb-2 font-semibold text-fg">{{ __('reports.revenue') }} — {{ __('reports.allBranches') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->branchBarOptions()))" @revenue-charts.window="update($event.detail.branch)"></div>
            </div>
        </x-ui.card>
        <x-ui.card>
            <h2 class="mb-2 font-semibold text-fg">{{ __('reports.revenue') }} — {{ __('reports.tabEmployees') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->employeeBarOptions()))" @revenue-charts.window="update($event.detail.employee)"></div>
            </div>
        </x-ui.card>
    </div>

    {{-- Breakdown table --}}
    <x-ui.card padding="p-0" class="mt-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('reports.tabEmployees') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('reports.kpiRevenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->revenue->by_employee as $e)
                        <tr wire:key="rev-emp-{{ $loop->index }}" class="border-b border-line last:border-0">
                            <td class="px-4 py-3 font-medium text-fg">{{ $e['employee_name'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-end font-medium tabular-nums text-primary-600">{{ Money::format((int) ($e['revenue'] ?? 0)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
