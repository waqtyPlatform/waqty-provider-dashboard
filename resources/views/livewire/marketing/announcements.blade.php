@php
    $priorityColors = ['high' => 'warning', 'normal' => 'info', 'low' => 'neutral'];
    $priorityLabels = ['high' => __('mkt.lblHigh'), 'normal' => __('mkt.lblNormal'), 'low' => __('mkt.lblLow')];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('mkt.announcements.title')" :subtitle="__('mkt.announcements.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('mkt.btnNewAnnouncement') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-0">
        @if (count($items) === 0)
            <x-ui.empty-state :title="__('mkt.lblNoAnnouncements')" :description="__('mkt.msgNoAnnouncementsDesc')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblAnnouncementTitle') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('mkt.lblPriority') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $a)
                            <tr wire:key="ann-{{ $a['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-fg">{{ $a['title'] }}</div>
                                    <div class="mt-0.5 line-clamp-1 max-w-md text-xs text-fg-muted">{{ $a['body'] }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$priorityColors[$a['priority']] ?? 'neutral'">{{ $priorityLabels[$a['priority']] ?? $a['priority'] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-ui.toggle :on="$a['active']" wire:click="toggleActive('{{ $a['id'] }}')" size="sm" />
                                        <span class="text-xs {{ $a['active'] ? 'text-success' : 'text-fg-subtle' }}">{{ $a['active'] ? __('common.active') : __('common.inactive') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $a['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $a['id'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
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
    <x-ui.slide-over wire="showForm" :title="$editingId ? __('mkt.lblEditAnnouncement') : __('mkt.lblCreateAnnouncement')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('mkt.lblAnnouncementTitle')" wire:model="form_title" :placeholder="__('mkt.phAnnouncementTitle')" maxlength="100" :error="$errors->first('form_title')" />
                <div>
                    <label for="ann-body" class="mb-1.5 block text-sm font-medium text-fg">{{ __('mkt.lblAnnouncementBody') }}</label>
                    <textarea id="ann-body" wire:model="form_body" rows="4" maxlength="500" placeholder="{{ __('mkt.phAnnouncementBody') }}"
                        class="w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->first('form_body') ? 'border-error' : 'border-line focus:border-primary-500' }}"></textarea>
                    @error('form_body')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <x-ui.select :label="__('mkt.lblPriority')" wire:model="form_priority" :options="['low' => __('mkt.lblLow'), 'normal' => __('mkt.lblNormal'), 'high' => __('mkt.lblHigh')]" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('mkt.lblActive') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteAnnouncement" :actionLabel="__('common.delete')" />
</div>
