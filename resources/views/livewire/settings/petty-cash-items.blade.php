@php
    $catLabels = [
        'administrative' => __('settings.petty.catAdministrative'),
        'kitchen' => __('settings.petty.catKitchen'),
        'maintenance' => __('settings.petty.catMaintenance'),
        'transportation' => __('settings.petty.catTransportation'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.petty.title')" :subtitle="__('settings.petty.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.petty.addItem') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.petty.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.petty.colCategory') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.petty.colLimit') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.petty.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $item)
                        <tr wire:key="pci-{{ $item->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $item->name }}</td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $catLabels[$item->category] ?? $item->category }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ \App\Support\Money::format($item->default_amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$item->active" wire:click="toggleActive('{{ $item->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $item->active ? 'text-success' : 'text-fg-subtle' }}">{{ $item->active ? __('settings.petty.statusActive') : __('settings.petty.statusInactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                    <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                    <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                        <button wire:click="openEdit('{{ $item->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                        <button wire:click="confirmDelete('{{ $item->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.petty.editModalTitle') : __('settings.petty.addModalTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.petty.itemName')" wire:model="form_name" :error="$errors->first('form_name')" />
                <x-ui.select :label="__('settings.petty.colCategory')" wire:model="form_category" :options="$catLabels" />
                <x-ui.input type="number" :label="__('settings.petty.limitEgp')" wire:model="form_limit" min="0" step="0.01" :error="$errors->first('form_limit')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('settings.petty.statusActive') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.petty.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ $editingUuid ? __('settings.petty.saveChanges') : __('settings.petty.saveItem') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.petty.deleteModalTitle')" :description="__('common.confirmDeleteDesc')" action="deleteItem" :actionLabel="__('common.delete')" />
</div>
