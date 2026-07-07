@php
    $channelLabels = [
        'sms' => __('mkt.messages.chSms'),
        'whatsapp' => __('mkt.messages.chWhatsapp'),
        'email' => __('mkt.messages.chEmail'),
        'push' => __('mkt.messages.chPush'),
    ];
    $channelColors = ['sms' => 'info', 'whatsapp' => 'success', 'email' => 'warning', 'push' => 'neutral'];
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('mkt.messages.title')" :subtitle="__('mkt.messages.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.btnNewTemplate') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if (count($items) === 0)
            <x-ui.empty-state :title="__('common.noData')" :description="__('mkt.messages.desc')" icon="megaphone" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblTemplateName') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblChannel') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblMessageBody') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $m)
                            <tr wire:key="msg-{{ $m['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $m['name'] }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$channelColors[$m['channel']] ?? 'neutral'">{{ $channelLabels[$m['channel']] ?? $m['channel'] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                                        <span class="line-clamp-1 max-w-xs text-fg-muted">{{ $m['body'] }}</span>
                                        <button type="button" @click="navigator.clipboard.writeText(@js($m['body'])); copied = true; setTimeout(() => copied = false, 1500)" class="shrink-0 text-fg-subtle hover:text-primary-600" :title="'{{ __('mkt.messages.copyBody') }}'">
                                            <x-icon name="check" class="size-4" x-show="copied" x-cloak />
                                            <x-icon name="copy" class="size-4" x-show="!copied" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $m['id'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $m['id'] }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
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
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.lblEditTemplate') : __('mkt.lblCreateNewTemplate')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.lblTemplateName')" wire:model="form_name" :placeholder="__('mkt.phTemplateName')" required :error="$errors->first('form_name')" />
                <x-ui.select :label="__('mkt.lblChannel')" wire:model="form_channel" required :options="$channelLabels" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('mkt.lblMessageBody') }}</label>
                    <textarea wire:model="form_body" rows="4" maxlength="300" placeholder="{{ __('mkt.phTypeMessage') }}" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('form_body') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteMessage" :actionLabel="__('common.delete')" />
</div>
