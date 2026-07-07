@php
    $dayLabels = [
        'sun' => __('emp.availability.daySun'),
        'mon' => __('emp.availability.dayMon'),
        'tue' => __('emp.availability.dayTue'),
        'wed' => __('emp.availability.dayWed'),
        'thu' => __('emp.availability.dayThu'),
        'fri' => __('emp.availability.dayFri'),
        'sat' => __('emp.availability.daySat'),
    ];
    $statusMeta = [
        'available' => ['label' => __('emp.availability.statusAvailable'), 'color' => 'success'],
        'on_leave' => ['label' => __('emp.availability.statusOnLeave'), 'color' => 'warning'],
        'off' => ['label' => __('emp.availability.statusOff'), 'color' => 'neutral'],
    ];
    $fmtHours = fn ($h) => rtrim(rtrim(number_format((float) $h, 1, '.', ''), '0'), '.');
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.availability.title')" :subtitle="__('emp.availability.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.availability.kpiAvailable')" :value="$this->kpis['available']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('emp.availability.kpiOnLeave')" :value="$this->kpis['onLeave']" icon="moon" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.availability.kpiTotal')" :value="$this->kpis['total']" icon="users" />
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="branchFilter" aria-label="{{ __('emp.availability.filterBranch') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('emp.availability.allBranches') }}</option>
            @foreach ($this->branchOptions as $branch)
                <option value="{{ $branch }}">{{ $branch }}</option>
            @endforeach
        </select>
        <select wire:model.live="employeeFilter" aria-label="{{ __('emp.availability.filterEmployee') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('emp.availability.allEmployees') }}</option>
            @foreach ($this->employeeOptions as $employee)
                <option value="{{ $employee }}">{{ $employee }}</option>
            @endforeach
        </select>
    </div>

    {{-- Employee availability cards --}}
    @if (count($this->filtered) === 0)
        <x-ui.empty-state :title="__('emp.availability.emptyTitle')" :description="__('emp.availability.emptyDesc')" icon="clock" />
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($this->filtered as $a)
                @php $meta = $statusMeta[$a['status']] ?? $statusMeta['available']; @endphp
                <x-ui.card wire:key="avail-{{ $a['uuid'] }}" class="flex flex-col gap-4">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-ui.avatar :name="$a['employee']" size="size-11" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">{{ $a['employee'] }}</p>
                                <p class="flex items-center gap-1.5 text-xs text-fg-subtle">
                                    <x-icon name="building-2" class="size-3.5" />{{ $a['branch'] ?: '—' }}
                                </p>
                            </div>
                        </div>
                        <x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge>
                    </div>

                    {{-- Weekly summary chips --}}
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700">
                            <x-icon name="calendar-check" class="size-3.5" />{{ __('emp.availability.summaryDays', ['count' => $a['days']]) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-info-light px-2.5 py-1 text-xs font-medium text-info">
                            <x-icon name="clock" class="size-3.5" />{{ __('emp.availability.summaryHours', ['count' => $fmtHours($a['hours'])]) }}
                        </span>
                    </div>

                    {{-- Weekly schedule --}}
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-fg-subtle">{{ __('emp.availability.scheduleLabel') }}</p>
                        @if (count($a['slots']) === 0)
                            <p class="text-sm text-fg-muted">{{ __('emp.availability.noSlots') }}</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($a['slots'] as $slot)
                                    <span class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface-2 px-2.5 py-1 text-xs text-fg-muted">
                                        <span class="font-medium text-fg">{{ $dayLabels[$slot['day']] ?? $slot['day'] }}</span>
                                        <span class="tabular-nums" dir="ltr">{{ $slot['from'] }}–{{ $slot['to'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
