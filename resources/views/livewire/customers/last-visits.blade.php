@php use Illuminate\Support\Carbon; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.lastVisits')" :subtitle="__('lastVisit.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('customers.totalClients')" :value="$this->kpis['total']" icon="users" />
        <x-ui.kpi-card :label="__('lastVisit.recent')" :value="$this->kpis['recent']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('lastVisit.followUp')" :value="$this->kpis['followUp']" icon="bell" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('lastVisit.overdue')" :value="$this->kpis['overdue']" icon="alert-triangle" iconClass="bg-error-light text-error" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="lv-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('customers.noClients')" icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('custProfile.statLastVisit') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $c)
                            @php
                                $days = $c->daysSince();
                                [$tone, $label] = match (true) {
                                    $days === null => ['neutral', __('lastVisit.never')],
                                    $days <= 7 => ['success', __('lastVisit.recent')],
                                    $days <= 30 => ['warning', $days.' '.__('lastVisit.daysAgo')],
                                    default => ['error', __('lastVisit.overdue')],
                                };
                            @endphp
                            <tr wire:key="lv-{{ $c->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$c->name" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $c->name }}</div>
                                            <span class="text-xs text-fg-subtle" dir="ltr">{{ $c->phone }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">
                                    {{ $c->last_booking_date ? Carbon::parse($c->last_booking_date)->isoFormat('D MMM YYYY') : '—' }}
                                    @if ($days !== null)<span class="text-xs text-fg-subtle">· {{ $days }} {{ __('lastVisit.daysAgo') }}</span>@endif
                                </td>
                                <td class="px-4 py-3"><x-ui.badge :color="$tone">{{ $label }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-end">
                                    @if ($c->needsFollowUp())
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-warning"><x-icon name="bell" class="size-3.5" />{{ __('lastVisit.followUp') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
