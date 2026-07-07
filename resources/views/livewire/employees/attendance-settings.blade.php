@php
    $overtimeOptions = [
        '1' => '1.0×',
        '1.25' => '1.25×',
        '1.5' => '1.5×',
        '1.75' => '1.75×',
        '2' => '2.0×',
    ];
@endphp

<div class="mx-auto max-w-3xl p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.attendanceSettings.title')" :subtitle="__('emp.attendanceSettings.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form wire:submit="save" class="space-y-6">
            {{-- Work hours --}}
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-fg">{{ __('emp.attendanceSettings.secHours') }}</h2>
                    <p class="mt-0.5 text-xs text-fg-subtle">{{ __('emp.attendanceSettings.secHoursHint') }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input type="time" :label="__('emp.attendanceSettings.shiftStart')" wire:model="form_shift_start" dir="ltr" :error="$errors->first('form_shift_start')" required />
                    <x-ui.input type="time" :label="__('emp.attendanceSettings.shiftEnd')" wire:model="form_shift_end" dir="ltr" :error="$errors->first('form_shift_end')" required />
                </div>
            </section>

            {{-- Thresholds & grace --}}
            <section class="space-y-4 border-t border-line pt-6">
                <div>
                    <h2 class="text-sm font-semibold text-fg">{{ __('emp.attendanceSettings.secThresholds') }}</h2>
                    <p class="mt-0.5 text-xs text-fg-subtle">{{ __('emp.attendanceSettings.secThresholdsHint') }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input type="number" min="0" max="240" :label="__('emp.attendanceSettings.lateThreshold')" wire:model="form_late_threshold" :hint="__('emp.attendanceSettings.minutesHint')" :error="$errors->first('form_late_threshold')" required />
                    <x-ui.input type="number" min="0" max="240" :label="__('emp.attendanceSettings.earlyLeaveThreshold')" wire:model="form_early_leave_threshold" :hint="__('emp.attendanceSettings.minutesHint')" :error="$errors->first('form_early_leave_threshold')" required />
                    <x-ui.input type="number" min="0" max="120" :label="__('emp.attendanceSettings.gracePeriod')" wire:model="form_grace_period" :hint="__('emp.attendanceSettings.gracePeriodHint')" :error="$errors->first('form_grace_period')" required />
                    <x-ui.input type="number" min="0" max="480" :label="__('emp.attendanceSettings.autoAbsentAfter')" wire:model="form_auto_absent_after" :hint="__('emp.attendanceSettings.autoAbsentAfterHint')" :error="$errors->first('form_auto_absent_after')" required />
                </div>
            </section>

            {{-- Overtime --}}
            <section class="space-y-4 border-t border-line pt-6">
                <div>
                    <h2 class="text-sm font-semibold text-fg">{{ __('emp.attendanceSettings.secOvertime') }}</h2>
                    <p class="mt-0.5 text-xs text-fg-subtle">{{ __('emp.attendanceSettings.secOvertimeHint') }}</p>
                </div>
                <div class="sm:max-w-xs">
                    <x-ui.select :label="__('emp.attendanceSettings.overtimeMultiplier')" wire:model="form_overtime_multiplier" :options="$overtimeOptions" :error="$errors->first('form_overtime_multiplier')" required />
                    @unless ($errors->has('form_overtime_multiplier'))
                        <p class="mt-1.5 text-xs text-fg-subtle">{{ __('emp.attendanceSettings.overtimeMultiplierHint') }}</p>
                    @endunless
                </div>
            </section>

            <div class="flex justify-end border-t border-line pt-5">
                <x-ui.button type="submit" icon="check" wire:loading.attr="disabled" wire:target="save">{{ __('emp.attendanceSettings.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
