<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('sidebar.groups')" :subtitle="__('custGroups.noGroupsDesc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('custGroups.newGroup') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if (count($this->groups) === 0)
        <x-ui.card>
            <x-ui.empty-state :title="__('custGroups.noGroups')" :description="__('custGroups.noGroupsDesc')" icon="users" />
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->groups as $g)
                <div wire:key="grp-{{ $g->uuid }}" class="relative overflow-hidden rounded-xl border border-line bg-surface p-5 shadow-xs">
                    <span class="absolute inset-y-0 start-0 w-1.5" style="background-color: {{ $g->color ?: '#64748b' }}"></span>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="size-4 shrink-0 rounded-full" style="background-color: {{ $g->color ?: '#64748b' }}"></span>
                            <h3 class="font-semibold text-fg">{{ $g->name }}</h3>
                        </div>
                        <x-ui.dropdown>
                            <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $g->uuid }}')">{{ __('custGroups.editGroup') }}</x-ui.dropdown-item>
                            <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $g->uuid }}')" destructive>{{ __('custGroups.deleteGroup') }}</x-ui.dropdown-item>
                        </x-ui.dropdown>
                    </div>
                    @if ($g->description)
                        <p class="mt-2 line-clamp-2 text-sm text-fg-muted">{{ $g->description }}</p>
                    @endif
                    <div class="mt-4 flex items-center gap-4 border-t border-line pt-4 text-sm">
                        <div class="flex items-center gap-1.5 text-fg-muted"><x-icon name="users" class="size-4 text-fg-subtle" />{{ $g->customers_count ?? 0 }} <span class="text-fg-subtle">{{ __('custGroups.members') }}</span></div>
                        @if ($g->discount_percentage)
                            <x-ui.badge color="success">{{ rtrim(rtrim(number_format($g->discount_percentage, 1), '0'), '.') }}% {{ __('custGroups.discount') }}</x-ui.badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('custGroups.editGroup') : __('custGroups.createGroup')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('custGroups.groupName')" wire:model="form_name" :placeholder="__('custGroups.groupNamePlaceholder')" :error="$errors->first('form_name')" required />
                <x-ui.input type="number" :label="__('custGroups.discountPercent')" wire:model="form_discount" min="0" max="100" step="0.5" :error="$errors->first('form_discount')" required />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('sales.lblColor') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="form_color" class="size-10 shrink-0 cursor-pointer rounded-lg border border-line bg-surface p-1">
                        <input type="text" wire:model="form_color" dir="ltr" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 font-mono text-sm text-fg focus:border-primary-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('custGroups.description') }}</label>
                    <textarea wire:model="form_description" rows="3" placeholder="{{ __('custGroups.descPlaceholder') }}" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('form_description') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ __('custGroups.saveGroup') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('custGroups.deleteGroup')" :description="__('common.confirmDeleteDesc')" action="deleteGroup" :actionLabel="__('common.delete')" />
</div>
