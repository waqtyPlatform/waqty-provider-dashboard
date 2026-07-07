<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.roles.title')" :subtitle="__('emp.roles.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.roles.newRole') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if (count($this->roles) === 0)
        <x-ui.card>
            <x-ui.empty-state icon="shield" :title="__('emp.roles.emptyTitle')" :description="__('emp.roles.emptyDesc')">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.roles.newRole') }}</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <x-ui.card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.roles.colRole') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.roles.colMembers') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.roles.colAccess') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->roles as $role)
                            <tr wire:key="role-{{ $role['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="grid size-9 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="shield" class="size-4.5" /></div>
                                        <div>
                                            <p class="font-medium text-fg">{{ $role['name'] }}</p>
                                            @if ($role['system'])
                                                <p class="text-xs text-fg-subtle">{{ __('emp.roles.systemRole') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-fg-muted">
                                        <x-icon name="users" class="size-4 text-fg-subtle" />
                                        <span class="tabular-nums">{{ $role['members'] }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$this->levelLabel($role) === __('emp.roles.levelFull') ? 'primary' : ($this->levelLabel($role) === __('emp.roles.levelNone') ? 'neutral' : 'info')">{{ $this->levelLabel($role) }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $role['uuid'] }}')">{{ __('emp.roles.editPerms') }}</x-ui.dropdown-item>
                                        @unless ($role['system'])
                                            <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $role['uuid'] }}')" destructive>{{ __('emp.roles.deleteRole') }}</x-ui.dropdown-item>
                                        @endunless
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.roles.editTitle') : __('emp.roles.createTitle')" maxWidth="max-w-2xl">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-5 p-5">
                <x-ui.input :label="__('emp.roles.roleName')" wire:model="form_name" :placeholder="__('emp.roles.namePh')" :error="$errors->first('form_name')" required />

                <div>
                    <p class="mb-2 text-sm font-semibold text-fg">{{ __('emp.roles.permsHeading') }}</p>
                    <div class="overflow-hidden rounded-lg border border-line">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line bg-surface-2 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                                    <th class="px-3 py-2.5 text-start font-semibold">{{ __('emp.roles.module') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('emp.roles.view') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('emp.roles.create') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('emp.roles.edit') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('emp.roles.delete') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Livewire\Employees\Roles::MODULES as $module)
                                    <tr wire:key="perm-{{ $module }}" class="border-b border-line last:border-0">
                                        <td class="px-3 py-2.5">
                                            <p class="font-medium text-fg">{{ __('emp.roles.mod.'.$module) }}</p>
                                            <div class="mt-1 flex gap-1">
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'full')" class="rounded px-1.5 py-0.5 text-[11px] text-primary-600 hover:bg-primary-50">{{ __('emp.roles.levelFull') }}</button>
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'view')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-2">{{ __('emp.roles.levelView') }}</button>
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'none')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-2">{{ __('emp.roles.levelNone') }}</button>
                                            </div>
                                        </td>
                                        @foreach (\App\Livewire\Employees\Roles::ACTIONS as $action)
                                            <td class="px-2 py-2.5 text-center">
                                                <input type="checkbox" wire:model="form_perms.{{ $module }}.{{ $action }}"
                                                    class="size-4 rounded border-line text-primary-600 focus:ring-primary-500/30" />
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('emp.roles.saveChanges') : __('emp.roles.saveRole') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.roles.deleteTitle')" :description="__('emp.roles.deleteWarning')" action="deleteRole" :actionLabel="__('emp.roles.deleteRole')" />
</div>
