<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.targets.title')" :subtitle="__('emp.targets.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.targets.newTarget') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.targets.kpiOnTrack')" :value="$this->kpis['onTrack']" icon="trending-up" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('emp.targets.kpiAchieved')" :value="$this->kpis['achieved']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('emp.targets.kpiTotalBonus')" :value="\App\Support\Money::format($this->kpis['bonus'])" icon="wallet" iconClass="bg-primary-50 text-primary-600" />
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if (count($this->items) === 0)
            <x-ui.empty-state :title="__('emp.targets.emptyTitle')" :description="__('emp.targets.emptyDesc')" icon="bar-chart-3">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.targets.newTarget') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.targets.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.targets.colType') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.targets.colTarget') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.targets.colProgress') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.targets.colBonus') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.targets.colPeriod') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->items as $t)
                            @php
                                $p = $t['progress'];
                                $bar = $p >= 100 ? 'bg-success' : ($p >= 70 ? 'bg-primary-500' : 'bg-warning');
                                $ptxt = $p >= 100 ? 'text-success' : ($p >= 70 ? 'text-fg' : 'text-warning');
                                $tier = rtrim(rtrim(number_format($t['tier'], 2, '.', ''), '0'), '.');
                            @endphp
                            <tr wire:key="target-{{ $t['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$t['employee']" />
                                        <span class="font-medium text-fg">{{ $t['employee'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$t['type'] === 'revenue' ? 'primary' : 'purple'">
                                        {{ $t['type'] === 'revenue' ? __('emp.targets.typeRevenue') : __('emp.targets.typeBookings') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold tabular-nums text-fg">
                                        @if ($t['type'] === 'revenue')
                                            {{ \App\Support\Money::format($t['target']) }}
                                        @else
                                            {{ number_format($t['target']) }} {{ __('emp.targets.bookingsUnit') }}
                                        @endif
                                    </p>
                                    <p class="text-xs tabular-nums text-fg-subtle">
                                        {{ __('emp.targets.achievedLabel') }}:
                                        @if ($t['type'] === 'revenue')
                                            {{ \App\Support\Money::format($t['achieved']) }}
                                        @else
                                            {{ number_format($t['achieved']) }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="w-44">
                                        <div class="mb-1 flex items-center justify-between gap-2">
                                            <span class="text-xs font-semibold tabular-nums {{ $ptxt }}">{{ $p }}%</span>
                                            <x-ui.badge color="neutral">×{{ $tier }}</x-ui.badge>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-3">
                                            <div class="h-full rounded-full {{ $bar }}" style="width: {{ min(100, max(0, $p)) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ \App\Support\Money::format($t['bonus']) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $t['period'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $t['uuid'] }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('emp.targets.editTitle') : __('emp.targets.createTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('emp.targets.employee')" wire:model="form_employee" :placeholder="__('emp.targets.employeePh')" :error="$errors->first('form_employee')" required />
                <x-ui.select :label="__('emp.targets.type')" wire:model.live="form_type" :options="['revenue' => __('emp.targets.typeRevenue'), 'bookings' => __('emp.targets.typeBookings')]" :error="$errors->first('form_type')" required />
                <x-ui.input
                    type="number"
                    :label="__('emp.targets.value')"
                    wire:model="form_value"
                    min="0"
                    step="0.01"
                    :hint="$form_type === 'revenue' ? __('emp.targets.valueRevenueHint') : __('emp.targets.valueBookingsHint')"
                    :error="$errors->first('form_value')"
                    required
                />
                <x-ui.input type="number" :label="__('emp.targets.tier')" wire:model="form_tier" min="1" step="0.05" :hint="__('emp.targets.tierHint')" :error="$errors->first('form_tier')" required />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('emp.targets.saveChanges') : __('emp.targets.create') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
