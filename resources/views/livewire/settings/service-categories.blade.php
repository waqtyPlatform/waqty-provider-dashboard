@php
    $colorOptions = [
        '#8b5cf6' => __('settings.serviceCategories.colors.purple'),
        '#ec4899' => __('settings.serviceCategories.colors.pink'),
        '#10b981' => __('settings.serviceCategories.colors.green'),
        '#f59e0b' => __('settings.serviceCategories.colors.orange'),
        '#3b82f6' => __('settings.serviceCategories.colors.blue'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.serviceCategories.title')" :subtitle="__('settings.serviceCategories.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.serviceCategories.newCategory') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.serviceCategories.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.serviceCategories.colColorTag') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.serviceCategories.colServicesCount') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $c)
                        <tr wire:key="cat-{{ $c->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $c->name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="size-3 rounded-full" style="background-color: {{ $c->color ?? '#8b5cf6' }}"></span>
                                    <span class="text-fg-muted">{{ $colorOptions[$c->color] ?? '' }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ $c->services_count }}</td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $c->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $c->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.serviceCategories.editTitle') : __('settings.serviceCategories.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.serviceCategories.categoryName')" wire:model="form_name" :placeholder="__('settings.serviceCategories.namePh')" :error="$errors->first('form_name')" :required="true" />
                <div>
                    <label class="mb-2 block text-sm font-medium text-fg">{{ __('settings.serviceCategories.colorTag') }}</label>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($colorOptions as $hex => $label)
                            <button type="button" wire:click="$set('form_color', '{{ $hex }}')" title="{{ $label }}"
                                class="size-9 rounded-full ring-2 ring-offset-2 ring-offset-surface transition {{ $form_color === $hex ? 'ring-fg' : 'ring-transparent' }}"
                                style="background-color: {{ $hex }}" aria-label="{{ $label }}"></button>
                        @endforeach
                    </div>
                </div>
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.serviceCategories.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('settings.serviceCategories.saveChanges') : __('settings.serviceCategories.saveCategory') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.serviceCategories.deleteTitle')" :description="__('settings.serviceCategories.deleteWarning')" action="deleteCategory" :actionLabel="__('common.delete')" />
</div>
