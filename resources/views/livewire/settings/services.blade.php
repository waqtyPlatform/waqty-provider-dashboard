<div class="mx-auto max-w-5xl p-6">
    <x-ui.page-header :title="__('settings.services.title')" :subtitle="__('sidebar.services')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.services.addService') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-56 flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-fg-subtle" />
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('settings.services.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 ps-9 pe-3 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
        </div>
        <select wire:model.live="categoryFilter"
            class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            <option value="all">{{ __('settings.services.colCategory') }}</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.services.colService') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.services.colCategory') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.services.colDuration') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.services.colPrice') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.services.colStatus') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->filtered as $s)
                        <tr wire:key="svc-{{ $s->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($s->image_url)
                                        <img src="{{ $s->image_url }}" alt="" class="size-10 shrink-0 rounded-lg object-cover" />
                                    @else
                                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600">
                                            <x-icon name="sparkles" class="size-5" />
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-fg">{{ $s->name }}</p>
                                        @if ($s->name_ar)
                                            <p class="truncate text-xs text-fg-subtle" dir="rtl">{{ $s->name_ar }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($s->categoryName())
                                    <x-ui.badge color="neutral">{{ $s->categoryName() }}</x-ui.badge>
                                @else
                                    <span class="text-fg-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-fg-muted">{{ $s->estimated_duration_minutes ?? '—' }} {{ __('settings.services.min') }}</td>
                            <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ $s->price ? \App\Support\Money::format($s->price) : '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.toggle :on="$s->active" wire:click="toggleActive('{{ $s->uuid }}')" size="sm" />
                                    <span class="text-xs {{ $s->active ? 'text-success' : 'text-fg-subtle' }}">{{ $s->active ? __('settings.services.active') : __('common.inactive') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                    <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                    <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                        <button wire:click="openEdit('{{ $s->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('settings.services.edit') }}</button>
                                        <button wire:click="confirmDelete('{{ $s->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('settings.services.delete') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12">
                                <x-ui.empty-state icon="sparkles" :title="__('settings.services.title')" :description="__('settings.services.search')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('settings.services.edit') : __('settings.services.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('settings.services.serviceName')" wire:model="form_name" :placeholder="__('settings.services.namePh')" :error="$errors->first('form_name')" />
                <x-ui.input label="الاسم بالعربية" wire:model="form_name_ar" dir="rtl" :error="$errors->first('form_name_ar')" />
                <x-ui.input :label="__('settings.services.category')" wire:model="form_category" list="svc-cats" :error="$errors->first('form_category')" />
                <datalist id="svc-cats">
                    @foreach ($this->categories as $cat)
                        <option value="{{ $cat }}"></option>
                    @endforeach
                </datalist>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input type="number" :label="__('settings.services.duration')" wire:model="form_duration" min="5" max="480" :error="$errors->first('form_duration')" />
                    <x-ui.input type="number" :label="__('settings.services.price')" wire:model="form_price" min="0" step="0.01" :error="$errors->first('form_price')" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.services.colService') }}</label>
                    <input type="file" wire:model="form_image" accept="image/*"
                        class="block w-full text-sm text-fg-muted file:me-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                    @error('form_image') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="form_image" class="mt-1.5 text-xs text-fg-subtle">…</div>
                </div>
                <x-ui.input :label="__('settings.services.new.desc')" wire:model="form_description" :error="$errors->first('form_description')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                    <span class="text-sm font-medium text-fg">{{ __('settings.services.active') }}</span>
                    <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('settings.services.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ $editingUuid ? __('settings.services.saveChanges') : __('settings.services.saveService') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('settings.services.confirmDelete')" :description="__('settings.services.deleteWarning')" action="deleteService" :actionLabel="__('settings.services.delete')" />
</div>
