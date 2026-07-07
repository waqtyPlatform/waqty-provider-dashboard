<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.fpDevices.title')" :subtitle="__('settings.fpDevices.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.fpDevices.newDevice') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.fpDevices.colDevice') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.fpDevices.colAddress') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.fpDevices.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $d)
                        <tr wire:key="fp-device-{{ $d->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <p class="font-medium text-fg">{{ $d->name }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs tabular-nums text-fg-muted">{{ $d->ip_address ? $d->ip_address . ':' . $d->port : '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$d->active" wire:click="toggleActive('{{ $d->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $d->active ? 'text-success' : 'text-fg-subtle' }}">{{ $d->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $d->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $d->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.fpDevices.editTitle') : __('settings.fpDevices.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.fpDevices.name')" wire:model="form_name" :placeholder="__('settings.fpDevices.namePh')" :error="$errors->first('form_name')" :required="true" />
                <x-ui.input :label="__('settings.fpDevices.ipAddress')" wire:model="form_ip" :placeholder="__('settings.fpDevices.ipPh')" :error="$errors->first('form_ip')" />
                <x-ui.input type="number" :label="__('settings.fpDevices.port')" wire:model="form_port" min="1" max="65535" :error="$errors->first('form_port')" :required="true" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('settings.fpDevices.saveChanges') : __('settings.fpDevices.createDevice') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.fpDevices.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteDevice" :actionLabel="__('common.delete')" />
</div>
