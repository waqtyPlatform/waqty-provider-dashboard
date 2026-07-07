@php
    $triggerLabels = [
        'booking_created' => __('settings.diaryAutomations.triggerBookingCreated'),
        'booking_cancelled' => __('settings.diaryAutomations.triggerBookingCancelled'),
        'no_show' => __('settings.diaryAutomations.triggerNoShow'),
        'birthday' => __('settings.diaryAutomations.triggerBirthday'),
    ];
    $actionLabels = [
        'send_sms' => __('settings.diaryAutomations.actionSendSms'),
        'send_email' => __('settings.diaryAutomations.actionSendEmail'),
        'send_whatsapp' => __('settings.diaryAutomations.actionSendWhatsapp'),
        'notify_staff' => __('settings.diaryAutomations.actionNotifyStaff'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.diaryAutomations.title')" :subtitle="__('settings.diaryAutomations.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.diaryAutomations.add') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.diaryAutomations.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.diaryAutomations.colTrigger') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.diaryAutomations.colAction') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.diaryAutomations.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $a)
                        <tr wire:key="da-{{ $a->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $a->name }}</td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $triggerLabels[$a->trigger] ?? $a->trigger }}</x-ui.badge></td>
                            <td class="px-4 py-3"><x-ui.badge color="info">{{ $actionLabels[$a->action] ?? $a->action }}</x-ui.badge></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$a->active" wire:click="toggleActive('{{ $a->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $a->active ? 'text-success' : 'text-fg-subtle' }}">{{ $a->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                    <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                    <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                        <button wire:click="openEdit('{{ $a->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                        <button wire:click="confirmDelete('{{ $a->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.diaryAutomations.editTitle') : __('settings.diaryAutomations.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.diaryAutomations.autoName')" wire:model="form_name" :placeholder="__('settings.diaryAutomations.namePh')" :error="$errors->first('form_name')" />
                <x-ui.select :label="__('settings.diaryAutomations.triggerLabel')" wire:model="form_trigger" :options="$triggerLabels" :error="$errors->first('form_trigger')" />
                <x-ui.select :label="__('settings.diaryAutomations.actionLabel')" wire:model="form_action" :options="$actionLabels" :error="$errors->first('form_action')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ $editingUuid ? __('settings.diaryAutomations.saveChanges') : __('settings.diaryAutomations.saveAutomation') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.diaryAutomations.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteAutomation" :actionLabel="__('common.delete')" />
</div>
