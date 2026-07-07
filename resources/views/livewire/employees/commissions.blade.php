@php
    use App\Support\Money;
    $tabs = [
        'by-service' => __('emp.commissions.tabByService'),
        'by-segment' => __('emp.commissions.tabBySegment'),
        'targets' => __('emp.commissions.tabTargets'),
        'recalc' => __('emp.commissions.tabRecalc'),
    ];
    $subjectHeader = match ($tab) {
        'by-segment' => __('emp.commissions.colSegment'),
        'targets' => __('emp.commissions.colTarget'),
        'recalc' => __('emp.commissions.colItem'),
        default => __('emp.commissions.colService'),
    };
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.commissions.title')" :subtitle="__('emp.commissions.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="inbox" wire:click="export">{{ __('emp.commissions.export') }}</x-ui.button>
            <x-ui.button variant="secondary" icon="wallet" wire:click="sendToPayroll">{{ __('emp.commissions.sendToPayroll') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Breakdown tabs --}}
    <div class="mb-4 flex gap-1 overflow-x-auto border-b border-line">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                class="relative whitespace-nowrap px-4 py-2.5 text-sm font-medium transition-colors {{ $tab === $key ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">
                {{ $label }}
                @if ($tab === $key)<span class="absolute inset-x-0 -bottom-px h-0.5 rounded bg-primary-500"></span>@endif
            </button>
        @endforeach
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.commissions.kpiTotal')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('emp.commissions.kpiTopEarner')" :value="$this->kpis['topEarner']" icon="star" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.commissions.kpiCount')" :value="$this->kpis['count']" icon="receipt" iconClass="bg-info-light text-info" />
    </div>

    {{-- Date range + employee filter + calculate --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="comm-from" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.commissions.dateFrom') }}</label>
            <input type="date" id="comm-from" wire:model.live="dateFrom" dir="ltr"
                class="rounded-lg border bg-surface px-3 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('dateFrom') border-error @else border-line focus:border-primary-500 @enderror">
            @error('dateFrom')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="comm-to" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.commissions.dateTo') }}</label>
            <input type="date" id="comm-to" wire:model.live="dateTo" dir="ltr"
                class="rounded-lg border bg-surface px-3 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('dateTo') border-error @else border-line focus:border-primary-500 @enderror">
            @error('dateTo')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
        <div class="min-w-48 flex-1">
            <label for="comm-employee" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.commissions.colEmployee') }}</label>
            <select id="comm-employee" wire:model.live="employeeFilter"
                class="w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="all">{{ __('emp.commissions.allEmployees') }}</option>
                @foreach ($this->employeeOptions() as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.button icon="gauge" wire:click="calculate" loadingTarget="calculate">{{ __('emp.commissions.calculate') }}</x-ui.button>
    </div>

    {{-- Recalculation panel --}}
    @if ($tab === 'recalc')
        <x-ui.card class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600"><x-icon name="rotate-ccw" class="size-5" /></span>
                    <div>
                        <h2 class="font-semibold text-fg">{{ __('emp.commissions.recalcTitle') }}</h2>
                        <p class="text-sm text-fg-muted">{{ __('emp.commissions.recalcDesc') }}</p>
                    </div>
                </div>
                <x-ui.button icon="gauge" wire:click="calculate" loadingTarget="calculate">{{ __('emp.commissions.calculate') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- Commission table --}}
    <x-ui.card padding="p-0">
        @if ($this->kpis['count'] === 0)
            <x-ui.empty-state :title="__('emp.commissions.empty')" :description="__('emp.commissions.emptyDesc')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $subjectHeader }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.commissions.colBase') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.commissions.colRate') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.commissions.colCommission') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->rows as $r)
                            <tr wire:key="comm-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$r['employee']" />
                                        <span class="font-medium text-fg">{{ $r['employee'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['subject'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($r['base']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ $r['rate'] }}%</td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums text-success">{{ Money::format($r['commission']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
