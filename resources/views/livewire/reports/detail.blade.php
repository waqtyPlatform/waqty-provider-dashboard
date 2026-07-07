@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    {{-- Breadcrumb: Reports / {category} / {report} --}}
    <nav aria-label="breadcrumb" class="mb-3 flex flex-wrap items-center gap-1.5 text-sm text-fg-muted">
        <a href="{{ route('reports') }}" wire:navigate class="hover:text-fg">{{ __('reports.title') }}</a>
        <span class="text-fg-subtle">/</span>
        <span>{{ $this->categoryTitle() }}</span>
        <span class="text-fg-subtle">/</span>
        <span class="font-medium text-fg">{{ $this->reportTitle() }}</span>
    </nav>

    <x-ui.page-header :title="$this->reportTitle()" :subtitle="__('reports.detailSubtitle')">
        <x-slot:actions>
            <div x-data="{ o: false }" @click.outside="o = false" class="relative">
                <x-ui.button icon="receipt" variant="secondary" x-on:click="o = !o">{{ __('reports.exportBtn') }}</x-ui.button>
                <div x-show="o" x-cloak @click="o = false"
                    class="absolute end-0 z-20 mt-1 w-44 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                    <x-ui.dropdown-item icon="receipt" wire:click="export('csv')">{{ __('reports.exportCsv') }}</x-ui.dropdown-item>
                    <x-ui.dropdown-item icon="receipt" wire:click="export('pdf')">{{ __('reports.exportPdf') }}</x-ui.dropdown-item>
                </div>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Filters: date range + branch --}}
    <div class="mb-6 flex flex-wrap items-end gap-3">
        <div class="inline-flex rounded-lg border border-line bg-surface p-0.5">
            @foreach (['30d' => __('reports.last30Days'), '3m' => __('reports.last3Months'), '6m' => __('reports.last6Months')] as $key => $lbl)
                <button type="button" wire:click="$set('range', '{{ $key }}')"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $key ? 'bg-primary-500 text-white' : 'text-fg-muted hover:text-fg' }}">{{ $lbl }}</button>
            @endforeach
        </div>
        <div class="min-w-48">
            <x-ui.select :label="__('reports.branch')" wire:model.live="branch" :options="$this->branchOptions()" />
        </div>
    </div>

    {{-- KPI strip --}}
    @if (count($this->kpis) > 0)
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($this->kpis as $kpi)
                <x-ui.kpi-card :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :iconClass="$kpi['iconClass']" />
            @endforeach
        </div>
    @endif

    {{-- Optional chart --}}
    @if ($this->hasChart())
        <x-ui.card class="mb-6">
            <h2 class="mb-2 font-semibold text-fg">{{ __('reports.chartBreakdown') }}</h2>
            <div wire:ignore>
                <div x-data="chart(@js($this->chartOptions()))" @report-detail-charts.window="update($event.detail.options)"></div>
            </div>
        </x-ui.card>
    @endif

    {{-- Searchable, sortable table --}}
    <x-ui.card padding="p-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4">
            <h2 class="font-semibold text-fg">{{ __('reports.tableDetails') }}</h2>
            <div class="relative min-w-56">
                <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
                <input type="search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                    class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
        </div>

        @if (count($this->displayRows) === 0)
            <div class="p-4">
                <x-ui.empty-state :title="__('common.noData')" :description="__('reports.detailSubtitle')" icon="bar-chart-3" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-fg-muted">
                            @foreach ($this->columns as $col)
                                <th class="px-4 py-3 font-medium {{ $col['numeric'] ? 'text-end' : 'text-start' }}">
                                    <button type="button" wire:click="sort('{{ $col['key'] }}')"
                                        class="inline-flex items-center gap-1 hover:text-fg {{ $col['numeric'] ? 'flex-row-reverse' : '' }}">
                                        <span>{{ $col['label'] }}</span>
                                        @if ($sortField === $col['key'])
                                            <x-icon name="chevron-down" class="size-3.5 transition-transform {{ $sortDir === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->displayRows as $i => $row)
                            <tr wire:key="row-{{ $i }}" class="border-b border-line/60 last:border-0 hover:bg-surface-2">
                                @foreach ($this->columns as $col)
                                    @php $val = $row[$col['key']] ?? null; @endphp
                                    <td class="px-4 py-3 {{ $col['numeric'] ? 'text-end tabular-nums text-fg' : 'text-fg-muted' }}">
                                        @switch($col['type'])
                                            @case('money')
                                                <span class="font-medium text-fg">{{ Money::format((int) $val) }}</span>
                                                @break
                                            @case('percent')
                                                {{ number_format((float) $val, 1) }}%
                                                @break
                                            @case('number')
                                                {{ number_format((int) $val) }}
                                                @break
                                            @case('status')
                                                <x-ui.status-pill :status="(string) $val" />
                                                @break
                                            @default
                                                <span class="text-fg">{{ $val }}</span>
                                        @endswitch
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
