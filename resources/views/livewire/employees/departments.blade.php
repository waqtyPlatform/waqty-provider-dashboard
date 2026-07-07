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
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $d['uuid'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $d['uuid'] }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
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
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('emp.departments.saveChanges') : __('emp.departments.createDepartment') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.departments.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteDepartment" :actionLabel="__('common.delete')" />
</div>
