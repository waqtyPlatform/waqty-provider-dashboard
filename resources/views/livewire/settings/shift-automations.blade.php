@php
    $triggerLabels = [
        'shift_start' => __('settings.shiftAutomations.trig.shift_start'),
        'shift_end' => __('settings.shiftAutomations.trig.shift_end'),
        'late_checkin' => __('settings.shiftAutomations.trig.late_checkin'),
        'missed_shift' => __('settings.shiftAutomations.trig.missed_shift'),
    ];
    $actionLabels = [
        'notify_manager' => __('settings.shiftAutomations.act.notify_manager'),
        'auto_clock_out' => __('settings.shiftAutomations.act.auto_clock_out'),
        'send_reminder' => __('settings.shiftAutomations.act.send_reminder'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.shiftAutomations.title')" :subtitle="__('settings.shiftAutomations.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.shiftAutomations.newAutomation') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftAutomations.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftAutomations.colTrigger') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftAutomations.colAction') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftAutomations.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $a)
                        <tr wire:key="automation-{{ $a->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $a->name }}</td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $triggerLabels[$a->trigger] ?? $a->trigger }}</x-ui.badge></td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $actionLabels[$a->action] ?? $a->action }}</x-ui.badge></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$a->active" wire:click="toggleActive('{{ $a->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $a->active ? 'text-success' : 'text-fg-subtle' }}">{{ $a->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $a->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $a->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.shiftAutomations.editTitle') : __('settings.shiftAutomations.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.shiftAutomations.name')" wire:model="form_name" :placeholder="__('settings.shiftAutomations.namePh')" :error="$errors->first('form_name')" :required="true" />
                <x-ui.select :label="__('settings.shiftAutomations.trigger')" wire:model="form_trigger" :options="$triggerLabels" :required="true" />
                <x-ui.select :label="__('settings.shiftAutomations.action')" wire:model="form_action" :options="$actionLabels" :required="true" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('settings.shiftAutomations.saveChanges') : __('settings.shiftAutomations.saveAutomation') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.shiftAutomations.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteAutomation" :actionLabel="__('common.delete')" />
</div>
