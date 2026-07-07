<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.branches.title')" :subtitle="__('settings.branches.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.branches.addBranch') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.branches.branchName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.branches.phone') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.branches.city') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $b)
                        <tr wire:key="branch-{{ $b->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <p class="font-medium text-fg">{{ $b->name }}</p>
                                @if ($b->is_main)
                                    <p class="text-xs text-fg-subtle">{{ __('settings.branches.mainBranch') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-fg-muted">{{ $b->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-fg-muted">{{ $b->city ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$b->active" wire:click="toggleActive('{{ $b->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $b->active ? 'text-success' : 'text-fg-subtle' }}">{{ $b->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $b->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $b->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.branches.editBranch') : __('settings.branches.addNewBranch')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.branches.branchName')" wire:model="form_name" :placeholder="__('settings.branches.namePh')" :error="$errors->first('form_name')" required />
                <x-ui.input :label="__('settings.branches.phone')" wire:model="form_phone" :error="$errors->first('form_phone')" />
                <x-ui.input :label="__('settings.branches.city')" wire:model="form_city" :placeholder="__('settings.branches.addressPh')" :error="$errors->first('form_city')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.branches.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ __('settings.branches.saveBranch') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.branches.confirmDelete')" :description="__('settings.branches.deleteWarning')" action="deleteBranch" :actionLabel="__('common.delete')" />
</div>
