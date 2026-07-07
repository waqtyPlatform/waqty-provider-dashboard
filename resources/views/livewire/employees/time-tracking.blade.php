@php
    use Illuminate\Support\Carbon;
    $hm = fn (int $m) => sprintf('%d:%02d', intdiv(max(0, $m), 60), max(0, $m) % 60);
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.timeTracking.title')" :subtitle="__('emp.timeTracking.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="secondary" wire:click="export" wire:loading.attr="disabled" wire:target="export">{{ __('common.export') }}</x-ui.button>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.timeTracking.manualEntry') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.timeTracking.kpiTotalHours')" :value="$this->kpis['totalHours']" icon="clock" iconClass="bg-primary-50 text-primary-600" />
        <x-ui.kpi-card :label="__('emp.timeTracking.kpiOvertime')" :value="$this->kpis['overtime']" icon="activity" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.timeTracking.kpiAvgPerDay')" :value="$this->kpis['avgPerDay']" icon="gauge" iconClass="bg-info-light text-info" />
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="employeeFilter" aria-label="{{ __('emp.timeTracking.colEmployee') }}" class="min-w-48 rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            @foreach ($this->employeeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
        </select>
        <div class="flex items-center gap-2">
            <label for="tt-from" class="text-sm text-fg-muted">{{ __('emp.timeTracking.from') }}</label>
            <input type="date" id="tt-from" wire:model.live="dateFrom" aria-label="{{ __('emp.timeTracking.from') }}"
                class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <div class="flex items-center gap-2">
            <label for="tt-to" class="text-sm text-fg-muted">{{ __('emp.timeTracking.to') }}</label>
            <input type="date" id="tt-to" wire:model.live="dateTo" aria-label="{{ __('emp.timeTracking.to') }}"
                class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('emp.timeTracking.emptyTitle')" :description="__('emp.timeTracking.emptyDesc')" icon="clock">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.timeTracking.manualEntry') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.timeTracking.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.timeTracking.colDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.timeTracking.colClockIn') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.timeTracking.colClockOut') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.timeTracking.colWorked') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.timeTracking.colOvertime') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $t)
                            <tr wire:key="tt-{{ $t['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$t['employee']" />
                                        <span class="font-medium text-fg">{{ $t['employee'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t['date'] ? Carbon::parse($t['date'])->isoFormat('ddd، D MMM') : '—' }}</td>
                                <td class="px-4 py-3 tabular-nums text-fg-muted" dir="ltr">{{ $t['clock_in'] ?: '—' }}</td>
                                <td class="px-4 py-3 tabular-nums" dir="ltr">
                                    @if ($t['clock_out'])
                                        <span class="text-fg-muted">{{ $t['clock_out'] }}</span>
                                    @else
                                        <x-ui.badge color="info">{{ __('emp.timeTracking.working') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ $t['clock_out'] ? $hm($t['worked_minutes']) : '—' }}</td>
                                <td class="px-4 py-3 text-end tabular-nums {{ $t['overtime_minutes'] > 0 ? 'font-semibold text-warning' : 'text-fg-subtle' }}">{{ $t['overtime_minutes'] > 0 ? $hm($t['overtime_minutes']) : '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $t['uuid'] }}')">{{ __('emp.timeTracking.adjust') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Manual entry / adjust slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.timeTracking.editTitle') : __('emp.timeTracking.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('emp.timeTracking.employee')" wire:model="form_employee" :placeholder="__('emp.timeTracking.employeePh')" :error="$errors->first('form_employee')" required />
                <x-ui.input type="date" :label="__('emp.timeTracking.date')" wire:model="form_date" :error="$errors->first('form_date')" required />
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input type="time" :label="__('emp.timeTracking.clockIn')" wire:model="form_clock_in" dir="ltr" :error="$errors->first('form_clock_in')" required />
                    <x-ui.input type="time" :label="__('emp.timeTracking.clockOut')" wire:model="form_clock_out" dir="ltr" :hint="__('emp.timeTracking.overtimeHint')" :error="$errors->first('form_clock_out')" required />
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('emp.timeTracking.saveChanges') : __('emp.timeTracking.create') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
