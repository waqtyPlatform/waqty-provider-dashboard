@php
    $statusMeta = [
        'present' => ['label' => __('emp.attendance.statusPresent'), 'color' => 'success'],
        'absent' => ['label' => __('emp.attendance.statusAbsent'), 'color' => 'error'],
        'late' => ['label' => __('emp.attendance.statusLate'), 'color' => 'warning'],
        'on_leave' => ['label' => __('emp.attendance.statusOnLeave'), 'color' => 'info'],
    ];
    $fmtHours = fn ($h) => rtrim(rtrim(number_format((float) $h, 1, '.', ''), '0'), '.');
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.attendance.title')" :subtitle="__('emp.attendance.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="receipt" wire:click="exportAttendance" wire:loading.attr="disabled" wire:target="exportAttendance">{{ __('common.export') }}</x-ui.button>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.attendance.addManual') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card :label="__('emp.attendance.kpiPresent')" :value="$this->kpis['present']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('emp.attendance.kpiLate')" :value="$this->kpis['late']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.attendance.kpiAbsent')" :value="$this->kpis['absent']" icon="ban" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('emp.attendance.kpiOnLeave')" :value="$this->kpis['onLeave']" icon="moon" iconClass="bg-info-light text-info" />
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="att-from" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.attendance.dateFrom') }}</label>
            <input type="date" id="att-from" wire:model.live="dateFrom"
                class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <div>
            <label for="att-to" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.attendance.dateTo') }}</label>
            <input type="date" id="att-to" wire:model.live="dateTo"
                class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <div>
            <label for="att-employee" class="mb-1.5 block text-xs font-medium text-fg-subtle">{{ __('emp.attendance.filterEmployee') }}</label>
            <select wire:model.live="employeeFilter" id="att-employee" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
                <option value="all">{{ __('emp.attendance.allEmployees') }}</option>
                @foreach ($this->employeeOptions as $employee)
                    <option value="{{ $employee }}">{{ $employee }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('emp.attendance.empty')" :description="__('emp.attendance.emptyDesc')" icon="calendar-check">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.attendance.addManual') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.attendance.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.attendance.colDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.attendance.colCheckIn') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.attendance.colCheckOut') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.attendance.colHours') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            @php $meta = $statusMeta[$r['status']] ?? $statusMeta['present']; @endphp
                            <tr wire:key="att-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $r['employee'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted tabular-nums" dir="ltr">{{ $r['date'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted tabular-nums" dir="ltr">{{ $r['check_in'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted tabular-nums" dir="ltr">{{ $r['check_out'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ $r['hours'] !== null ? $fmtHours($r['hours']).' '.__('emp.attendance.hoursUnit') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown :ariaLabel="__('common.actions')">
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $r['uuid'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="trash-2" destructive wire:click="confirmDelete('{{ $r['uuid'] }}')">{{ __('common.delete') }}</x-ui.dropdown-item>
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

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.attendance.editTitle') : __('emp.attendance.addTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('emp.attendance.lblEmployee')" wire:model="form_employee" required
                    :placeholder="__('emp.attendance.employeePlaceholder')"
                    :options="collect($this->employeeOptions)->mapWithKeys(fn ($e) => [$e => $e])->toArray()"
                    :error="$errors->first('form_employee')" />
                <x-ui.input type="date" :label="__('emp.attendance.lblDate')" wire:model="form_date" required :error="$errors->first('form_date')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="time" :label="__('emp.attendance.lblCheckIn')" wire:model="form_check_in" :error="$errors->first('form_check_in')" />
                    <x-ui.input type="time" :label="__('emp.attendance.lblCheckOut')" wire:model="form_check_out" :error="$errors->first('form_check_out')" />
                </div>
                <x-ui.select :label="__('emp.attendance.lblStatus')" wire:model="form_status" required
                    :options="collect($this->statuses())->mapWithKeys(fn ($s) => [$s => $statusMeta[$s]['label']])->toArray()"
                    :error="$errors->first('form_status')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ $editingUuid ? __('common.save') : __('emp.attendance.addManual') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.attendance.deleteTitle')" :description="__('emp.attendance.deleteDesc')"
        action="deleteAttendance" :actionLabel="__('common.delete')" />
</div>
