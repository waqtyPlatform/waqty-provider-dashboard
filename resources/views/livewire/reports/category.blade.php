<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__($this->def()['title'])" :subtitle="__('reports.subtitle')">
        <x-slot:actions>
            <div class="w-40 sm:w-48">
                <x-ui.select wire:model.live="branch" aria-label="{{ __('reports.allBranches') }}" :options="[
                    '' => __('reports.allBranches'),
                    'downtown' => __('reports.downtown'),
                    'mall' => __('reports.mall'),
                    'new-cairo' => __('reports.newCairo'),
                ]" />
            </div>
            <div class="inline-flex rounded-lg border border-line bg-surface p-0.5">
                @foreach (['1m' => __('reports.thisMonth'), '3m' => __('reports.last3Months'), '6m' => __('reports.last6Months')] as $key => $lbl)
                    <button type="button" wire:click="$set('range', '{{ $key }}')" class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $key ? 'bg-primary-500 text-white' : 'text-fg-muted hover:text-fg' }}">{{ $lbl }}</button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPI row (from the report summary) --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($this->kpis as $kpi)
            <x-ui.kpi-card wire:key="kpi-{{ $loop->index }}" :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :iconClass="$kpi['iconClass']" />
        @endforeach
    </div>

    {{-- Single category chart --}}
    <x-ui.card class="mb-6">
        <h2 class="mb-2 font-semibold text-fg">{{ __($this->def()['chart']['title']) }}</h2>
        <div wire:ignore>
            <div x-data="chart(@js($this->chartOptions()))" @reports-charts.window="update($event.detail.chart)"></div>
        </div>
    </x-ui.card>

    {{-- Drill-down sub-reports --}}
    <h2 class="mb-3 text-lg font-semibold text-fg">{{ __('reports.detailedReports') }}</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->def()['reports'] as $rpt)
            <a href="/reports/{{ $category }}/{{ $rpt['slug'] }}" wire:navigate wire:key="rpt-{{ $rpt['slug'] }}"
                class="group flex items-start gap-3 rounded-xl border border-line bg-surface p-4 transition-colors hover:border-primary-500/40 hover:bg-surface-2">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600">
                    <x-icon :name="$rpt['icon']" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-fg">{{ __($rpt['title']) }}</p>
                    <p class="mt-0.5 text-sm text-fg-muted">{{ __($rpt['desc']) }}</p>
                </div>
                <x-icon name="chevron-right" class="mt-1 size-4 shrink-0 text-fg-subtle transition-colors group-hover:text-primary-600 rtl:rotate-180" />
            </a>
        @endforeach
    </div>
</div>
