@php
    $colorOptions = [
        '#8b5cf6' => __('settings.serviceCategories.colors.purple'),
        '#ec4899' => __('settings.serviceCategories.colors.pink'),
        '#10b981' => __('settings.serviceCategories.colors.green'),
        '#f59e0b' => __('settings.serviceCategories.colors.orange'),
        '#3b82f6' => __('settings.serviceCategories.colors.blue'),
    ];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('mkt.serviceGroups.title')" :subtitle="__('mkt.serviceGroups.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.serviceGroups.newGroup') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (count($this->items) === 0)
        <x-ui.card><x-ui.empty-state :title="__('common.noData')" :description="__('mkt.serviceGroups.desc')" /></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->items as $g)
                <div wire:key="grp-{{ $g['id'] }}" class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-xs {{ $g['active'] ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $g['color'] }}"></span>
                            <h3 class="font-semibold text-fg">{{ $g['name'] }}</h3>
                        </div>
                        <x-ui.toggle :on="$g['active']" wire:click="toggleActive('{{ $g['id'] }}')" size="sm" />
                    </div>
                    <div class="my-3 flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-primary-600">{{ $g['servicesCount'] }}</span>
                        <span class="text-sm font-medium text-fg-subtle">{{ __('mkt.serviceGroups.servicesCount') }}</span>
                    </div>
                    <div class="border-t border-line pt-3">
                        <x-ui.badge :color="$g['active'] ? 'success' : 'neutral'">{{ $g['active'] ? __('common.active') : __('common.inactive') }}</x-ui.badge>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" class="flex-1" wire:click="openEdit('{{ $g['id'] }}')" icon="pencil">{{ __('common.edit') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="confirmDelete('{{ $g['id'] }}')" icon="trash-2" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.serviceGroups.editTitle') : __('mkt.serviceGroups.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.serviceGroups.name')" wire:model="form_name" :placeholder="__('mkt.serviceGroups.namePh')" :error="$errors->first('form_name')" />
                <div>
                    <label class="mb-2 block text-sm font-medium text-fg">{{ __('mkt.serviceGroups.colorTag') }}</label>
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
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteGroup" :actionLabel="__('common.delete')" />
</div>
