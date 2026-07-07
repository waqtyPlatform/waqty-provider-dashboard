<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.departments.title')" :subtitle="__('emp.departments.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.departments.newDepartment') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if (count($this->departments) === 0)
        <x-ui.card>
            <x-ui.empty-state icon="users" :title="__('emp.departments.emptyTitle')" :description="__('emp.departments.emptyDesc')">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.departments.newDepartment') }}</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <x-ui.card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.departments.colName') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.departments.colDescription') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.departments.colEmployees') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->departments as $d)
                            <tr wire:key="dept-{{ $d['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $d['name'] }}</td>
                                <td class="px-4 py-3 text-fg-muted">
                                    <span class="line-clamp-1 max-w-xs">{{ $d['description'] ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-fg-muted">
                                        <x-icon name="users" class="size-4 text-fg-subtle" />
                                        <span class="tabular-nums">{{ $d['employees_count'] }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $d['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $d['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.departments.editTitle') : __('emp.departments.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('emp.departments.name')" wire:model="form_name" :placeholder="__('emp.departments.namePh')" :error="$errors->first('form_name')" required />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('emp.departments.description') }}</label>
                    <textarea wire:model="form_description" rows="3" placeholder="{{ __('emp.departments.descPh') }}" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('form_description') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ $editingUuid ? __('emp.departments.saveChanges') : __('emp.departments.createDepartment') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.departments.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteDepartment" :actionLabel="__('common.delete')" />
</div>
