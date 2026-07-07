@php use Illuminate\Support\Carbon; use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('sidebar.clientAccounts')" :subtitle="__('clAcc.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('customers.totalClients')" :value="$this->kpis['total']" icon="users" />
        <x-ui.kpi-card :label="__('clAcc.totalBookings')" :value="$this->kpis['bookings']" icon="calendar-check" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('clAcc.withBookings')" :value="$this->kpis['withBookings']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('clAcc.avgPerClient')" :value="$this->kpis['avg']" icon="activity" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="clacc-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('customers.noClients')" :description="__('customers.noClientsDesc')" icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('employees.phoneOrEmail') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('clAcc.colBookings') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('clAcc.colLastBooking') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $c)
                            <tr wire:key="clacc-{{ $c->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$c->name" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $c->name }}</div>
                                            <span class="text-xs text-fg-subtle">{{ $c->uuid }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-fg-muted" dir="ltr"><x-icon name="phone" class="size-3.5" />{{ $c->phone ?: '—' }}</div>
                                    @if ($c->email)<div class="mt-0.5 flex items-center gap-1.5 text-xs text-fg-subtle" dir="ltr"><x-icon name="mail" class="size-3.5" />{{ $c->email }}</div>@endif
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ $c->total_bookings }}</td>
                                <td class="px-4 py-3 {{ $c->last_booking_date ? 'text-fg-muted' : 'text-fg-subtle' }}">{{ $c->last_booking_date ? Carbon::parse($c->last_booking_date)->isoFormat('D MMM YYYY') : '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.button variant="ghost" size="sm" wire:click="openHistory('{{ $c->uuid }}')" icon="calendar-days">{{ __('clAcc.bookingHistory') }}</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Booking-history slide-over --}}
    <x-ui.slide-over wire="showHistory" :title="__('clAcc.bookingHistory')">
        <div class="flex-1 overflow-y-auto p-5">
            <p class="mb-4 text-sm text-fg-muted">{{ $historyName }}</p>
            @if (count($this->history) === 0)
                <x-ui.empty-state :title="__('clAcc.noBookings')" icon="calendar-days" />
            @else
                <ul class="space-y-3">
                    @foreach ($this->history as $b)
                        <li wire:key="hist-{{ $b->uuid }}" class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">{{ $b->serviceName() }}</p>
                                <p class="text-xs text-fg-subtle">{{ $b->booking_date ? Carbon::parse($b->booking_date)->isoFormat('D MMM YYYY') : '' }} · {{ $b->hhmm() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-ui.status-pill :status="$b->status" :label="$b->statusEnum()->label()" />
                                <span class="text-sm font-medium tabular-nums text-primary-600">{{ $b->price ? Money::format($b->price) : '' }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-ui.slide-over>
</div>
