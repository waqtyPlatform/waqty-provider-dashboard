@php
    use Illuminate\Support\Carbon;

    $statusLabels = [
        'waiting' => __('waitlist.waiting'),
        'notified' => __('waitlist.notified'),
        'booked' => __('waitlist.booked'),
        'cancelled' => __('waitlist.cancelled'),
    ];
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('waitlist.title')" :subtitle="__('waitlist.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('waitlist.waiting')" :value="$this->kpis['waiting']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('waitlist.notified')" :value="$this->kpis['notified']" icon="bell" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('waitlist.booked')" :value="$this->kpis['booked']" icon="calendar-check" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('dash.total')" :value="$this->kpis['total']" icon="users" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="statusFilter" id="waitlist-status" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('waitlist.allStatuses') }}</option>
            <option value="waiting">{{ __('waitlist.waiting') }}</option>
            <option value="notified">{{ __('waitlist.notified') }}</option>
            <option value="booked">{{ __('waitlist.booked') }}</option>
            <option value="cancelled">{{ __('waitlist.cancelled') }}</option>
        </select>
    </div>

    {{-- List --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('waitlist.empty.title')" :description="__('waitlist.empty.desc')" icon="clock" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">#</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('sales.lblServices') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('sales.lblDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->filtered as $w)
                            <tr wire:key="wl-{{ $w->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <x-ui.badge color="neutral">#{{ $w->position }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar :name="$w->customerName()" class="size-8 text-xs" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $w->customerName() }}</div>
                                            <div class="text-xs text-fg-subtle" dir="ltr">{{ $w->customerPhone() ?: '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">{{ $w->serviceName() }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-fg">{{ $w->preferred_date ? Carbon::parse($w->preferred_date)->isoFormat('D MMM') : '—' }}</div>
                                    @if ($w->hhmm())
                                        <div class="text-xs tabular-nums text-fg-subtle">{{ $w->hhmm() }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.status-pill :status="$w->status" :label="$statusLabels[$w->status] ?? $w->status" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($w->status === 'waiting')
                                            <x-ui.button variant="outline" size="sm" icon="bell" wire:click="notify('{{ $w->uuid }}')">{{ __('waitlist.notify') }}</x-ui.button>
                                        @endif
                                        <x-ui.button variant="ghost" size="sm" icon="trash-2" wire:click="confirmRemove('{{ $w->uuid }}')" class="text-error hover:bg-error-light">{{ __('common.delete') }}</x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Remove confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="remove" :actionLabel="__('common.delete')" />
</div>
