@php
    $channels = ['sms' => __('mkt.campaigns.chSms'), 'email' => __('mkt.campaigns.chEmail'), 'whatsapp' => __('mkt.campaigns.chWhatsapp'), 'push' => __('mkt.campaigns.chPush')];
    $statuses = ['draft' => __('mkt.campaigns.stDraft'), 'active' => __('mkt.campaigns.stActive'), 'ended' => __('mkt.campaigns.stEnded')];
    $audiences = ['all' => __('mkt.campaigns.audAll'), 'vip' => __('mkt.campaigns.audVip'), 'inactive' => __('mkt.campaigns.audInactive')];
    $statusColor = ['active' => 'success', 'draft' => 'neutral', 'ended' => 'warning'];
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('mkt.campaigns.title')" :subtitle="__('mkt.campaigns.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.campaigns.new') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if (count($items) === 0)
            <x-ui.empty-state :title="__('common.noData')" :description="__('mkt.campaigns.desc')" icon="megaphone" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.campaigns.name') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.campaigns.channel') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.campaigns.audience') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $c)
                            <tr wire:key="camp-{{ $c['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $c['name'] }}</td>
                                <td class="px-4 py-3"><x-ui.badge color="info">{{ $channels[$c['channel']] ?? $c['channel'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-fg-muted">{{ $audiences[$c['audience']] ?? $c['audience'] }}</td>
                                <td class="px-4 py-3"><x-ui.badge :color="$statusColor[$c['status']] ?? 'neutral'">{{ $statuses[$c['status']] ?? $c['status'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $c['id'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $c['id'] }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.campaigns.edit') : __('mkt.campaigns.new')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.campaigns.name')" wire:model="form_name" :placeholder="__('mkt.campaigns.namePh')" required :error="$errors->first('form_name')" />
                <x-ui.select :label="__('mkt.campaigns.channel')" wire:model="form_channel" required :options="$channels" :error="$errors->first('form_channel')" />
                <x-ui.select :label="__('common.status')" wire:model="form_status" required :options="$statuses" :error="$errors->first('form_status')" />
                <x-ui.select :label="__('mkt.campaigns.audience')" wire:model="form_audience" required :options="$audiences" :error="$errors->first('form_audience')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('mkt.campaigns.deleteTitle')" :description="__('common.confirmDeleteDesc')" action="deleteCampaign" :actionLabel="__('common.delete')" />
</div>
