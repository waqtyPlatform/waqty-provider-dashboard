<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.shiftTemplates.title')" :subtitle="__('settings.shiftTemplates.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.shiftTemplates.newTemplate') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftTemplates.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftTemplates.colHours') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.shiftTemplates.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $t)
                        <tr wire:key="shift-{{ $t->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <p class="font-medium text-fg">{{ $t->name }}</p>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-fg-muted">{{ $t->start_time }} – {{ $t->end_time }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$t->active" wire:click="toggleActive('{{ $t->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $t->active ? 'text-success' : 'text-fg-subtle' }}">{{ $t->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $t->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $t->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.shiftTemplates.editTitle') : __('settings.shiftTemplates.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.shiftTemplates.templateName')" wire:model="form_name" :placeholder="__('settings.shiftTemplates.namePh')" :error="$errors->first('form_name')" :required="true" />
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.shiftTemplates.startTime') }}</label>
                        <input type="time" wire:model="form_start_time" class="w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->first('form_start_time') ? 'border-error' : 'border-line focus:border-primary-500' }}">
                        @error('form_start_time') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.shiftTemplates.endTime') }}</label>
                        <input type="time" wire:model="form_end_time" class="w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->first('form_end_time') ? 'border-error' : 'border-line focus:border-primary-500' }}">
                        @error('form_end_time') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('settings.shiftTemplates.saveChanges') : __('settings.shiftTemplates.createBtn') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.shiftTemplates.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteTemplate" :actionLabel="__('common.delete')" />
</div>
