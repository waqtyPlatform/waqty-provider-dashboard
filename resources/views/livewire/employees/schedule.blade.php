@php
    $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    $dayLabels = [
        'sun' => __('emp.schedule.daySun'),
        'mon' => __('emp.schedule.dayMon'),
        'tue' => __('emp.schedule.dayTue'),
        'wed' => __('emp.schedule.dayWed'),
        'thu' => __('emp.schedule.dayThu'),
        'fri' => __('emp.schedule.dayFri'),
        'sat' => __('emp.schedule.daySat'),
    ];
    $fmtHours = fn ($h) => rtrim(rtrim(number_format((float) $h, 1, '.', ''), '0'), '.');
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.schedule.title')" :subtitle="__('emp.schedule.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.schedule.addShift') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Week navigation --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5">
            <button type="button" wire:click="prevWeek" aria-label="{{ __('emp.schedule.prevWeek') }}"
                class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2">
                <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />
            </button>
            <button type="button" wire:click="thisWeek"
                class="rounded-lg border border-line px-3 py-2 text-sm font-medium text-fg hover:bg-surface-2">
                {{ __('emp.schedule.thisWeek') }}
            </button>
            <button type="button" wire:click="nextWeek" aria-label="{{ __('emp.schedule.nextWeek') }}"
                class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2">
                <x-icon name="chevron-right" class="size-4 rtl:rotate-180" />
            </button>
        </div>

        <p class="flex items-center gap-2 text-sm font-medium text-fg">
            <x-icon name="calendar-days" class="size-4 text-fg-subtle" />{{ $this->weekRangeLabel() }}
        </p>

        <div class="flex items-center gap-3 text-xs text-fg-subtle">
            <span class="inline-flex items-center gap-1.5">
                <x-icon name="calendar-check" class="size-3.5" />{{ __('emp.schedule.shiftsCount', ['count' => $this->summary['count']]) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <x-icon name="clock" class="size-3.5" />{{ __('emp.schedule.hoursCount', ['count' => $fmtHours($this->summary['hours'])]) }}
            </span>
        </div>
    </div>

    {{-- Weekly grid --}}
    <x-ui.card padding="p-0">
        @if (count($this->roster) === 0)
            <x-ui.empty-state :title="__('emp.schedule.emptyTitle')" :description="__('emp.schedule.emptyDesc')" icon="calendar-days">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.schedule.addShift') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="sticky start-0 z-10 border-b border-line bg-surface px-4 py-3 text-start font-semibold">
                                {{ __('emp.schedule.employee') }}
                            </th>
                            @foreach ($this->weekDays as $wd)
                                <th class="border-b border-line px-2 py-3 text-center font-semibold {{ $wd['isToday'] ? 'bg-primary-50' : '' }}">
                                    <span class="block text-fg">{{ $dayLabels[$wd['key']] }}</span>
                                    <span class="mt-0.5 block text-[11px] font-normal normal-case text-fg-subtle">{{ $wd['dayNum'] }} {{ $wd['monShort'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->roster as $row)
                            <tr wire:key="row-{{ $loop->index }}" class="hover:bg-surface-2/50">
                                <td class="sticky start-0 z-10 border-b border-line bg-surface px-4 py-3 align-middle">
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar :name="$row['employee']" size="size-9" />
                                        <span class="truncate font-medium text-fg">{{ $row['employee'] }}</span>
                                    </div>
                                </td>
                                @foreach ($days as $day)
                                    @php $cell = $row['days'][$day]; @endphp
                                    <td class="border-b border-line px-1.5 py-1.5 align-top {{ $this->weekDays[array_search($day, $days, true)]['isToday'] ? 'bg-primary-50/40' : '' }}">
                                        <div class="group flex min-h-16 flex-col gap-1">
                                            @foreach ($cell as $shift)
                                                <button type="button" wire:key="chip-{{ $shift['uuid'] }}" wire:click="openEdit('{{ $shift['uuid'] }}')"
                                                    class="w-full rounded-lg bg-primary-50 px-2 py-1.5 text-center text-xs font-medium text-primary-700 transition hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                                                    <span class="tabular-nums" dir="ltr">{{ $shift['start'] }}–{{ $shift['end'] }}</span>
                                                </button>
                                            @endforeach

                                            @if (count($cell) === 0)
                                                <button type="button" wire:click="openCreate('{{ $row['employee'] }}', '{{ $day }}')"
                                                    aria-label="{{ __('emp.schedule.addShift') }}"
                                                    class="grid min-h-16 w-full place-items-center rounded-lg text-fg-subtle transition hover:bg-surface-2 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                                    <x-icon name="plus" class="size-4 opacity-40 group-hover:opacity-100" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="openCreate('{{ $row['employee'] }}', '{{ $day }}')"
                                                    aria-label="{{ __('emp.schedule.addShift') }}"
                                                    class="flex w-full items-center justify-center rounded-lg py-0.5 text-fg-subtle opacity-0 transition hover:bg-surface-2 hover:text-primary-600 focus:opacity-100 focus:outline-none group-hover:opacity-100">
                                                    <x-icon name="plus" class="size-3.5" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Add / edit shift slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.schedule.editTitle') : __('emp.schedule.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('emp.schedule.employee')" wire:model="form_employee" required :error="$errors->first('form_employee')">
                    <option value="">{{ __('emp.schedule.selectEmployee') }}</option>
                    @foreach ($this->employeeNames as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select :label="__('emp.schedule.day')" wire:model="form_day" required :error="$errors->first('form_day')">
                    <option value="">{{ __('emp.schedule.selectDay') }}</option>
                    @foreach ($days as $day)
                        <option value="{{ $day }}">{{ $dayLabels[$day] }}</option>
                    @endforeach
                </x-ui.select>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="time" :label="__('emp.schedule.startTime')" wire:model="form_start" required :error="$errors->first('form_start')" />
                    <x-ui.input type="time" :label="__('emp.schedule.endTime')" wire:model="form_end" required :error="$errors->first('form_end')" />
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('emp.schedule.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
                    {{ $editingUuid ? __('emp.schedule.saveChanges') : __('emp.schedule.addShift') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
