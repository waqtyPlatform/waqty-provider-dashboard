@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.deductions.title')" :subtitle="__('emp.deductions.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.deductions.add') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.deductions.kpiTotal')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('emp.deductions.kpiThisMonth')" :value="Money::compact($this->kpis['thisMonth'])" icon="calendar-check" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('emp.deductions.kpiCount')" :value="$this->kpis['count']" icon="receipt" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="ded-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('emp.deductions.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="typeFilter" aria-label="{{ __('emp.deductions.type') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('emp.deductions.allTypes') }}</option>
            @foreach ($this->types() as $type)
                <option value="{{ $type }}">{{ __('emp.deductions.type'.ucfirst($type)) }}</option>
            @endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('emp.deductions.empty')" :description="__('emp.deductions.emptyDesc')" icon="receipt">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.deductions.add') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.deductions.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.deductions.colType') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.deductions.colAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.deductions.colReason') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.deductions.colDate') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            @php
                                $type = $r['type'] ?? 'other';
                                $typeColor = match ($type) {
                                    'absence' => 'error',
                                    'late' => 'warning',
                                    'penalty' => 'purple',
                                    'advance' => 'info',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr wire:key="ded-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $r['employee'] ?? '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge :color="$typeColor">{{ __('emp.deductions.type'.ucfirst($type)) }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format((int) ($r['amount'] ?? 0)) }}</td>
                                <td class="max-w-xs px-4 py-3 text-fg-muted"><span class="line-clamp-1">{{ $r['reason'] ?? '—' }}</span></td>
                                <td class="px-4 py-3 text-fg-muted tabular-nums" dir="ltr">{{ $r['date'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown :ariaLabel="__('common.actions')">
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $r['uuid'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="trash-2" destructive wire:click="confirmDelete('{{ $r['uuid'] }}')">{{ __('common.delete') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.deductions.editTitle') : __('emp.deductions.addTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('emp.deductions.lblEmployee')" wire:model="form_employee" required
                    :placeholder="__('emp.deductions.employeePlaceholder')"
                    :options="collect($this->employeeOptions())->mapWithKeys(fn ($e) => [$e => $e])->toArray()"
                    :error="$errors->first('form_employee')" />
                <x-ui.select :label="__('emp.deductions.lblType')" wire:model="form_type" required
                    :options="collect($this->types())->mapWithKeys(fn ($t) => [$t => __('emp.deductions.type'.ucfirst($t))])->toArray()"
                    :error="$errors->first('form_type')" />
                <x-ui.input type="number" :label="__('emp.deductions.lblAmount')" wire:model="form_amount" min="0" step="0.01" required :error="$errors->first('form_amount')" />
                <x-ui.input :label="__('emp.deductions.lblReason')" wire:model="form_reason" required :error="$errors->first('form_reason')" />
                <x-ui.input type="date" :label="__('emp.deductions.lblDate')" wire:model="form_date" required :error="$errors->first('form_date')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ $editingUuid ? __('common.save') : __('emp.deductions.add') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.deductions.deleteTitle')" :description="__('emp.deductions.deleteDesc')"
        action="deleteDeduction" :actionLabel="__('common.delete')" />
</div>
