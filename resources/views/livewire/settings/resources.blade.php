@php
    $typeLabels = [
        'chair' => __('settings.resources.typeChair'),
        'room' => __('settings.resources.typeRoom'),
        'equipment' => __('settings.resources.typeEquip'),
    ];
    $statusLabels = [
        'active' => __('settings.resources.statusActive'),
        'maintenance' => __('settings.resources.statusMaint'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.resources.title')" :subtitle="__('settings.resources.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.resources.add') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.resources.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.resources.colType') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.resources.colCapacity') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.resources.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $r)
                        <tr wire:key="res-{{ $r->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $r->name }}</td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $typeLabels[$r->type] ?? $r->type }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ $r->capacity }} {{ __('settings.resources.persons') }}</td>
                            <td class="px-4 py-3"><x-ui.badge :color="$r->status === 'active' ? 'success' : 'warning'">{{ $statusLabels[$r->status] ?? $r->status }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $r->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $r->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.resources.editTitle') : __('settings.resources.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.resources.resName')" wire:model="form_name" :placeholder="__('settings.resources.namePh')" :error="$errors->first('form_name')" :required="true" />
                <x-ui.select :label="__('settings.resources.resType')" wire:model="form_type" :options="$typeLabels" :required="true" />
                <x-ui.input type="number" :label="__('settings.resources.capPersons')" wire:model="form_capacity" min="1" max="99" :error="$errors->first('form_capacity')" :required="true" />
                <x-ui.select :label="__('settings.resources.colStatus')" wire:model="form_status" :options="$statusLabels" :required="true" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.resources.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('settings.resources.saveChanges') : __('settings.resources.saveResource') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.resources.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteResource" :actionLabel="__('common.delete')" />
</div>
