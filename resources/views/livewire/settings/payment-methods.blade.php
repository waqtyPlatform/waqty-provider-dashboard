@php
    $typeLabels = [
        'cash' => __('settings.paymentMethods.types.cash'),
        'card' => __('settings.paymentMethods.types.card'),
        'wallet' => __('settings.paymentMethods.types.wallet'),
        'bank_transfer' => __('settings.paymentMethods.types.bank'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.paymentMethods.title')" :subtitle="__('settings.paymentMethods.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.paymentMethods.addMethod') }}</x-ui.button>
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
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.paymentMethods.colName') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.paymentMethods.colType') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.paymentMethods.colFee') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.paymentMethods.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->methods as $m)
                        <tr wire:key="pm-{{ $m->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $m->name }}</td>
                            <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $typeLabels[$m->type] ?? $m->type }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ rtrim(rtrim(number_format($m->fee_percentage, 2), '0'), '.') }}%</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$m->active" wire:click="toggleActive('{{ $m->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $m->active ? 'text-success' : 'text-fg-subtle' }}">{{ $m->active ? __('common.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $m->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $m->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.paymentMethods.editTitle') : __('settings.paymentMethods.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.paymentMethods.methodName')" wire:model="form_name" :error="$errors->first('form_name')" :required="true" />
                <x-ui.select :label="__('settings.paymentMethods.methodType')" wire:model="form_type" :options="$typeLabels" :required="true" />
                <x-ui.input type="number" :label="__('settings.paymentMethods.transactionFee')" wire:model="form_fee" min="0" max="100" step="0.1" :error="$errors->first('form_fee')" :required="true" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('common.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ __('settings.paymentMethods.saveMethod') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteMethod" :actionLabel="__('common.delete')" />
</div>
