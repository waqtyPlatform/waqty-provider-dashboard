<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.positions.title')" :subtitle="__('emp.positions.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.positions.addPosition') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @php
        $levelColors = ['junior' => 'neutral', 'mid' => 'info', 'senior' => 'primary'];
        $levelLabels = ['junior' => __('emp.positions.levelJunior'), 'mid' => __('emp.positions.levelMid'), 'senior' => __('emp.positions.levelSenior')];
    @endphp

    @if (count($this->items) === 0)
        <x-ui.empty-state icon="user-cog" :title="__('emp.positions.emptyTitle')" :description="__('emp.positions.emptyDesc')">
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.positions.addPosition') }}</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.positions.colTitle') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.positions.colDepartment') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.positions.colLevel') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.positions.colSalaryRange') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->items as $p)
                            <tr wire:key="pos-{{ $p['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $p['title'] }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $p['department'] ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$levelColors[$p['level']] ?? 'neutral'">{{ $levelLabels[$p['level']] ?? $p['level'] }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg">
                                    {{ \App\Support\Money::format($p['salary_min']) }} – {{ \App\Support\Money::format($p['salary_max']) }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $p['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $p['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.positions.editTitle') : __('emp.positions.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('emp.positions.fieldTitle')" wire:model="form_title" :placeholder="__('emp.positions.titlePlaceholder')" :error="$errors->first('form_title')" required />
                <x-ui.select :label="__('emp.positions.fieldDepartment')" wire:model="form_department" :placeholder="__('emp.positions.departmentPlaceholder')" :error="$errors->first('form_department')">
                    @foreach ($this->departmentOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select :label="__('emp.positions.fieldLevel')" wire:model="form_level" :error="$errors->first('form_level')" required>
                    <option value="junior">{{ __('emp.positions.levelJunior') }}</option>
                    <option value="mid">{{ __('emp.positions.levelMid') }}</option>
                    <option value="senior">{{ __('emp.positions.levelSenior') }}</option>
                </x-ui.select>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input type="number" :label="__('emp.positions.fieldSalaryMin')" wire:model="form_salary_min" min="0" step="0.01" :error="$errors->first('form_salary_min')" required />
                    <x-ui.input type="number" :label="__('emp.positions.fieldSalaryMax')" wire:model="form_salary_max" min="0" step="0.01" :error="$errors->first('form_salary_max')" required />
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit">{{ $editingUuid ? __('emp.positions.saveChanges') : __('emp.positions.createPosition') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('emp.positions.deleteTitle')" :description="__('emp.positions.deleteWarning')" action="deletePosition" :actionLabel="__('common.delete')" />
</div>
