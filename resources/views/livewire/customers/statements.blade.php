@php use Illuminate\Support\Carbon; use App\Support\Money; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.statements')" :subtitle="__('stmt.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('stmt.charged')" :value="Money::compact($this->kpis['charged'])" icon="receipt" />
        <x-ui.kpi-card :label="__('stmt.paid')" :value="Money::compact($this->kpis['paid'])" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('stmt.outstanding')" :value="Money::compact($this->kpis['outstanding'])" icon="alert-triangle" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('customers.totalClients')" :value="$this->kpis['clients']" icon="users" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="stmt-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('clAcc.colBookings') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('stmt.charged') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('stmt.paid') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('stmt.outstanding') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('clAcc.colLastBooking') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            <tr wire:key="stmt-{{ $r->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$r->name" class="size-8 text-xs" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $r->name }}</div>
                                            <span class="text-xs text-fg-subtle" dir="ltr">{{ $r->phone }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">
                                    {{ $r->total_bookings }}
                                    <span class="text-xs text-success">· {{ $r->completed_bookings }} {{ __('stmt.colCompleted') }}</span>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg">{{ Money::format($r->total_charged) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-success">{{ Money::format($r->total_paid) }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums {{ $r->outstanding > 0 ? 'text-error' : 'text-fg-subtle' }}">{{ Money::format($r->outstanding) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r->last_booking_date ? Carbon::parse($r->last_booking_date)->isoFormat('D MMM') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>
</div>
