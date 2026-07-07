@php
    use App\Support\Money;
    $tabs = [
        'service' => __('emp.commissions.tabService'),
        'tier' => __('emp.commissions.tabTiers'),
        'segment' => __('emp.commissions.tabSegments'),
    ];
    $addLabels = [
        'service' => __('emp.commissions.addService'),
        'tier' => __('emp.commissions.addTier'),
        'segment' => __('emp.commissions.addSegment'),
    ];
    $formTitles = [
        'service' => $editingUuid ? __('emp.commissions.editServiceTitle') : __('emp.commissions.createServiceTitle'),
        'tier' => $editingUuid ? __('emp.commissions.editTierTitle') : __('emp.commissions.createTierTitle'),
        'segment' => $editingUuid ? __('emp.commissions.editSegmentTitle') : __('emp.commissions.createSegmentTitle'),
    ];
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.commissions.title')" :subtitle="__('emp.commissions.subtitle')">
        <x-slot:actions>
            <x-ui.badge color="amber"><x-icon name="shield" class="size-3.5" />{{ __('emp.commissions.adminOnly') }}</x-ui.badge>
            @if ($this->isAdmin())
                <x-ui.button icon="check" wire:click="saveAll" wire:loading.attr="disabled" wire:target="saveAll">{{ __('emp.commissions.saveAll') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @unless ($this->isAdmin())
        <x-ui.card>
            <x-ui.empty-state icon="ban" :title="__('emp.commissions.deniedTitle')" :description="__('emp.commissions.deniedDesc')" />
        </x-ui.card>
    @else
        @if ($this->usingFallback())
            <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
        @endif

        {{-- KPIs --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.kpi-card :label="__('emp.commissions.kpiTotal')" :value="$this->kpis['total']" icon="receipt" iconClass="bg-primary-50 text-primary-600" />
            <x-ui.kpi-card :label="__('emp.commissions.kpiActive')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
            <x-ui.kpi-card :label="__('emp.commissions.kpiServices')" :value="$this->kpis['services']" icon="scissors" iconClass="bg-info-light text-info" />
        </div>

        {{-- General settings --}}
        <x-ui.card class="mb-6">
            <h2 class="mb-4 font-semibold text-fg">{{ __('emp.commissions.generalTitle') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <label class="flex items-center justify-between gap-3 rounded-lg border border-line px-3.5 py-2.5 sm:col-span-3">
                    <span>
                        <span class="block text-sm font-medium text-fg">{{ __('emp.commissions.enabled') }}</span>
                        <span class="block text-xs text-fg-subtle">{{ __('emp.commissions.enabledHint') }}</span>
                    </span>
                    <x-ui.toggle :on="$enabled" wire:click="$toggle('enabled')" />
                </label>
                <x-ui.input type="number" :label="__('emp.commissions.baseRate')" wire:model="baseRate" min="0" max="100" step="0.5" :hint="__('emp.commissions.baseRateHint')" :error="$errors->first('baseRate')" required />
                <div class="sm:col-span-2">
                    <x-ui.select
                        :label="__('emp.commissions.payoutCycle')"
                        wire:model="payoutCycle"
                        :options="[
                            'monthly' => __('emp.commissions.cycleMonthly'),
                            'biweekly' => __('emp.commissions.cycleBiweekly'),
                            'weekly' => __('emp.commissions.cycleWeekly'),
                        ]"
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Tabs --}}
        <div class="mb-4 flex gap-1 overflow-x-auto border-b border-line">
            @foreach ($tabs as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')" class="relative whitespace-nowrap px-4 py-2.5 text-sm font-medium transition-colors {{ $tab === $key ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">
                    {{ $label }}
                    @if ($tab === $key)<span class="absolute inset-x-0 -bottom-px h-0.5 rounded bg-primary-500"></span>@endif
                </button>
            @endforeach
        </div>

        <div class="mb-4 flex justify-end">
            <x-ui.button variant="secondary" icon="plus" wire:click="openCreate('{{ $tab }}')">{{ $addLabels[$tab] }}</x-ui.button>
        </div>

        <x-ui.card padding="p-0">
            @if (count($this->visibleRules) === 0)
                @php
                    $emptyTitle = ['service' => 'emptyServiceTitle', 'tier' => 'emptyTierTitle', 'segment' => 'emptySegmentTitle'][$tab];
                    $emptyDesc = ['service' => 'emptyServiceDesc', 'tier' => 'emptyTierDesc', 'segment' => 'emptySegmentDesc'][$tab];
                @endphp
                <x-ui.empty-state icon="receipt" :title="__('emp.commissions.'.$emptyTitle)" :description="__('emp.commissions.'.$emptyDesc)">
                    <x-ui.button icon="plus" wire:click="openCreate('{{ $tab }}')">{{ $addLabels[$tab] }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                                @if ($tab === 'service')
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colService') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colRate') }}</th>
                                @elseif ($tab === 'tier')
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colTierName') }}</th>
                                    <th class="px-4 py-3 text-end font-semibold">{{ __('emp.commissions.colThreshold') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colMultiplier') }}</th>
                                @else
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colSegment') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold">{{ __('emp.commissions.colAdjust') }}</th>
                                @endif
                                <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->visibleRules as $rule)
                                <tr wire:key="rule-{{ $rule['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                    @if ($tab === 'service')
                                        <td class="px-4 py-3 font-medium text-fg">{{ $rule['label'] }}</td>
                                        <td class="px-4 py-3 font-semibold tabular-nums text-primary-600">{{ rtrim(rtrim(number_format($rule['rate'], 2, '.', ''), '0'), '.') }}%</td>
                                    @elseif ($tab === 'tier')
                                        <td class="px-4 py-3 font-medium text-fg">{{ $rule['label'] }}</td>
                                        <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ Money::format($rule['threshold']) }}</td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge color="purple">×{{ rtrim(rtrim(number_format($rule['multiplier'], 2, '.', ''), '0'), '.') }}</x-ui.badge>
                                        </td>
                                    @else
                                        <td class="px-4 py-3 font-medium text-fg">{{ $rule['label'] }}</td>
                                        <td class="px-4 py-3 font-semibold tabular-nums text-success">+{{ rtrim(rtrim(number_format($rule['rate'], 2, '.', ''), '0'), '.') }}%</td>
                                    @endif
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <x-ui.toggle :on="$rule['active']" wire:click="toggleActive('{{ $rule['uuid'] }}')" size="sm" />
                                            <span class="text-xs {{ $rule['active'] ? 'text-success' : 'text-fg-subtle' }}">{{ $rule['active'] ? __('emp.commissions.active') : __('emp.commissions.inactive') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                            <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                            <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                                <button wire:click="openEdit('{{ $rule['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                                <button wire:click="confirmDelete('{{ $rule['uuid'] }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
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

        {{-- Create / edit slide-over --}}
        <x-ui.slide-over wire="showForm" :title="$formTitles[$form_kind] ?? ''">
            <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
                <div class="flex-1 space-y-4 p-5">
                    @if ($form_kind === 'service')
                        <x-ui.input :label="__('emp.commissions.fldServiceName')" wire:model="form_label" :placeholder="__('emp.commissions.fldServicePh')" :error="$errors->first('form_label')" required />
                    @elseif ($form_kind === 'tier')
                        <x-ui.input :label="__('emp.commissions.fldTierName')" wire:model="form_label" :placeholder="__('emp.commissions.fldTierPh')" :error="$errors->first('form_label')" required />
                    @else
                        <x-ui.input :label="__('emp.commissions.fldSegmentName')" wire:model="form_label" :placeholder="__('emp.commissions.fldSegmentPh')" :error="$errors->first('form_label')" required />
                    @endif

                    @if ($form_kind === 'tier')
                        <x-ui.input type="number" :label="__('emp.commissions.fldThreshold')" wire:model="form_threshold" min="0" step="0.01" :hint="__('emp.commissions.fldThresholdHint')" :error="$errors->first('form_threshold')" required />
                        <x-ui.input type="number" :label="__('emp.commissions.fldMultiplier')" wire:model="form_multiplier" min="1" step="0.05" :hint="__('emp.commissions.fldMultiplierHint')" :error="$errors->first('form_multiplier')" required />
                    @else
                        <x-ui.input type="number" :label="__('emp.commissions.fldRate')" wire:model="form_rate" min="0" max="100" step="0.5" :hint="$form_kind === 'segment' ? __('emp.commissions.fldAdjustHint') : __('emp.commissions.fldRateHint')" :error="$errors->first('form_rate')" required />
                    @endif
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                    <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ $editingUuid ? __('emp.commissions.saveChanges') : __('emp.commissions.create') }}</x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>

        <x-ui.confirm-dialog wire="showDelete" :title="__('emp.commissions.deleteTitle')" :description="__('emp.commissions.deleteWarning')" action="deleteRule" :actionLabel="__('common.delete')" />
    @endunless
</div>
