@php use App\Support\Money; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.services')" :subtitle="__('sales.lblServices')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('settings.services.addService') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') ?? 'Showing sample data — the live API is unavailable.' }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('sidebar.services')" :value="$this->kpis['total']" icon="sparkles" />
        <x-ui.kpi-card :label="__('sales.active')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('sidebar.svcCategories')" :value="$this->kpis['categories']" icon="tag" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('common.avgPrice')" :value="Money::format($this->kpis['avgPrice'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle">
                <x-icon name="search" class="size-4" />
            </span>
            <input
                type="search"
                id="services-search"
                aria-label="{{ __('common.search') }}"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
        </div>
        <select wire:model.live="categoryFilter" id="services-category" aria-label="{{ __('sidebar.svcCategories') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.all') }}</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
    </div>

    {{-- Cards --}}
    @if ($this->total === 0)
        <x-ui.card>
            <x-ui.empty-state
                :title="__('sales.noServicesFound')"
                :description="__('sales.noServicesDesc')"
                icon="sparkles"
            />
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->paginated as $s)
                <div wire:key="svc-{{ $s->uuid }}" class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-xs transition-shadow hover:shadow-md {{ $s->active ? '' : 'opacity-70' }}">
                    <div class="relative flex h-32 items-center justify-center bg-gradient-to-br from-primary-50 to-surface-2 dark:from-primary-900/20">
                        @if ($s->image_url)
                            <img src="{{ $s->image_url }}" alt="{{ $s->displayName() }}" class="h-full w-full object-cover">
                        @else
                            <x-icon name="sparkles" class="size-8 text-primary-400" />
                        @endif
                        <div class="absolute end-2 top-2">
                            <div x-data="{ o: false }" @click.outside="o = false" class="relative">
                                <button @click="o = !o" class="grid size-8 place-items-center rounded-lg bg-surface/80 text-fg-subtle backdrop-blur hover:bg-surface"><x-icon name="more-vertical" class="size-4" /></button>
                                <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                    <button wire:click="openEdit('{{ $s->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                    <button wire:click="confirmDelete('{{ $s->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-fg">{{ $s->displayName() }}</h3>
                                @if ($s->categoryName())
                                    <x-ui.badge color="neutral" size="sm">{{ $s->categoryName() }}</x-ui.badge>
                                @endif
                            </div>
                            <x-ui.toggle :on="$s->active" wire:click="toggleActive('{{ $s->uuid }}')" size="sm" />
                        </div>
                        @if ($s->description)
                            <p class="mt-2 line-clamp-2 text-sm text-fg-muted">{{ $s->description }}</p>
                        @endif
                        <div class="mt-auto flex items-center justify-between pt-3">
                            <span class="flex items-center gap-1.5 text-sm text-fg-subtle"><x-icon name="clock" class="size-3.5" />{{ $s->estimated_duration_minutes ?? '—' }} {{ __('sales.minutesShort') ?? 'min' }}</span>
                            <span class="font-semibold tabular-nums text-primary-600">{{ $s->price ? Money::format($s->price) : '—' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        </div>
    @endif

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('common.edit').' '.__('sidebar.services') : __('settings.services.addService')">
            <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
                <div class="flex-1 space-y-4 p-5">
                    <x-ui.input :label="__('sales.lblServices')" wire:model="form_name" :error="$errors->first('form_name')" />
                    <x-ui.input label="الاسم بالعربية" wire:model="form_name_ar" dir="rtl" :error="$errors->first('form_name_ar')" />
                    <x-ui.input :label="__('sidebar.svcCategories')" wire:model="form_category" :error="$errors->first('form_category')" list="svc-categories" />
                    <datalist id="svc-categories">
                        @foreach ($this->categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input type="number" :label="__('sales.minutesShort')" wire:model="form_duration" min="5" max="480" :error="$errors->first('form_duration')" />
                        <x-ui.input type="number" :label="__('sales.lblRegularPrice')" wire:model="form_price" min="0" step="0.01" :error="$errors->first('form_price')" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('sales.lblNotes') ?? 'Description' }}</label>
                        <textarea wire:model="form_description" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                        @error('form_description') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('common.image') }}</label>
                        <input type="file" wire:model="form_image" accept="image/*" class="block w-full text-sm text-fg-muted file:me-3 file:rounded-lg file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-sm file:text-fg hover:file:bg-surface-3">
                        <div wire:loading wire:target="form_image" class="mt-1 text-xs text-fg-subtle">{{ __('common.loading') }}</div>
                        @error('form_image') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                        <span class="text-sm font-medium text-fg">{{ __('sales.active') }}</span>
                        <x-ui.toggle :on="$form_active" wire:click="$toggle('form_active')" />
                    </label>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                    <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save,form_image">{{ __('common.save') }}</x-ui.button>
                </div>
            </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteService" :actionLabel="__('common.delete')" />
</div>
