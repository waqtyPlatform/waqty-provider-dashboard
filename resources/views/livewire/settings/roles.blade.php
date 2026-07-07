<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('sidebar.roles')" :subtitle="__('settings.roles.granularPerms')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.roles.newRole') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.roles.colRole') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.roles.colMembers') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr wire:key="role-{{ $role['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="grid size-9 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="shield" class="size-4.5" /></div>
                                    <div>
                                        <p class="font-medium text-fg">{{ $role['name'] }}</p>
                                        @if ($role['system'] ?? false)
                                            <p class="text-xs text-fg-subtle">{{ __('settings.roles.levelFull') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-fg-muted">{{ $role['members'] }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :color="$this->levelLabel($role) === __('settings.roles.levelFull') ? 'primary' : ($this->levelLabel($role) === __('settings.roles.levelNone') ? 'neutral' : 'info')">{{ $this->levelLabel($role) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $role['id'] }}')">{{ __('settings.roles.editPerms') }}</x-ui.dropdown-item>
                                    @unless ($role['system'] ?? false)
                                        <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $role['id'] }}')" destructive>{{ __('settings.roles.deleteRole') }}</x-ui.dropdown-item>
                                    @endunless
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-fg-subtle">{{ __('settings.roles.emptyDesc') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingId ? __('settings.roles.editTitle') : __('settings.roles.createTitle')" maxWidth="max-w-2xl">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-5 p-5">
                <x-ui.input :label="__('settings.roles.roleName')" wire:model="form_name" :placeholder="__('settings.roles.namePh')" :error="$errors->first('form_name')" required />

                <div>
                    <p class="mb-2 text-sm font-semibold text-fg">{{ __('settings.roles.granularPerms') }}</p>
                    <div class="overflow-hidden rounded-lg border border-line">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line bg-surface-2 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                                    <th class="px-3 py-2.5 text-start font-semibold">{{ __('settings.roles.module') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('settings.roles.view') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('settings.roles.create') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('settings.roles.edit') }}</th>
                                    <th class="px-2 py-2.5 text-center font-semibold">{{ __('settings.roles.delete') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Livewire\Settings\Roles::MODULES as $module)
                                    <tr wire:key="perm-{{ $module }}" class="border-b border-line last:border-0">
                                        <td class="px-3 py-2.5">
                                            <p class="font-medium text-fg">{{ __('settings.roles.mod.'.$module) }}</p>
                                            <div class="mt-1 flex gap-1">
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'full')" class="rounded px-1.5 py-0.5 text-[11px] text-primary-600 hover:bg-primary-50">{{ __('settings.roles.levelFull') }}</button>
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'view')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-2">{{ __('settings.roles.levelView') }}</button>
                                                <button type="button" wire:click="setLevel('{{ $module }}', 'none')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-2">{{ __('settings.roles.levelNone') }}</button>
                                            </div>
                                        </td>
                                        @foreach (\App\Livewire\Settings\Roles::ACTIONS as $action)
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
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.roles.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingId ? __('settings.roles.saveChanges') : __('settings.roles.saveRole') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.roles.deleteConfirmTitle')" :description="__('settings.roles.deleteWarning')" action="deleteRole" :actionLabel="__('settings.roles.deleteRole')" />
</div>
