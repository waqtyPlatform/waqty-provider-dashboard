@php
    $audiences = [
        'all' => __('mkt.notifications.audAll'),
        'vip' => __('mkt.notifications.audVip'),
        'inactive' => __('mkt.notifications.audInactive'),
    ];
    $audienceColors = ['all' => 'info', 'vip' => 'warning', 'inactive' => 'neutral'];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('mkt.notifications.title')" :subtitle="__('mkt.notifications.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.notifications.new') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if (count($this->items) === 0)
            <x-ui.empty-state :title="__('common.noData')" :description="__('mkt.notifications.desc')" icon="megaphone" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.notifications.colTitle') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.notifications.colAudience') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.notifications.colSent') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->items as $n)
                            <tr wire:key="ntf-{{ $n['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-fg">{{ $n['title'] }}</div>
                                    <div class="mt-0.5 max-w-md truncate text-xs text-fg-muted">{{ $n['body'] }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$audienceColors[$n['audience']] ?? 'neutral'">{{ $audiences[$n['audience']] ?? $n['audience'] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($n['sentAt'] === 'Draft')
                                        <span class="text-xs font-medium text-fg-subtle">{{ __('mkt.notifications.draft') }}</span>
                                    @else
                                        <span class="text-xs text-fg-muted">{{ $n['sentAt'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $n['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $n['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.notifications.edit') : __('mkt.notifications.create')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.notifications.lblTitle')" wire:model="form_title" maxlength="80" :placeholder="__('mkt.notifications.titlePh')" :error="$errors->first('form_title')" />
                <div>
                    <label for="ntf-body" class="mb-1.5 block text-sm font-medium text-fg">{{ __('mkt.notifications.lblBody') }}</label>
                    <textarea id="ntf-body" wire:model="form_body" rows="4" maxlength="200" placeholder="{{ __('mkt.notifications.bodyPh') }}"
                        class="w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->has('form_body') ? 'border-error' : 'border-line focus:border-primary-500' }}"></textarea>
                    @error('form_body')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
                <x-ui.select :label="__('mkt.notifications.lblAudience')" wire:model="form_audience" :options="$audiences" :error="$errors->first('form_audience')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteNotification" :actionLabel="__('common.delete')" />
</div>
