@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
@endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.bookingList')" :subtitle="__('sidebar.bookings')">
        <x-slot:actions>
            <x-ui.button href="{{ route('bookings.new') }}" wire:navigate icon="plus">{{ __('dash.newBooking') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('dash.total')" :value="$this->kpis['total']" icon="calendar-days" />
        <x-ui.kpi-card :label="__('dash.statusConfirmed')" :value="$this->kpis['confirmed']" icon="calendar-check" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('dash.statusCompleted')" :value="$this->kpis['completed']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('dash.colRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="bookings-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="statusFilter" id="bookings-status" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.allStatuses') }}</option>
            @foreach (\App\Enums\BookingStatus::cases() as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('common.noData')" :description="__('sidebar.bookings')" icon="calendar-days" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('sales.lblDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('sales.lblServices') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['staff'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('dash.colRevenue') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $b)
                            <tr wire:key="bk-{{ $b->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-fg">{{ $b->booking_date ? Carbon::parse($b->booking_date)->isoFormat('D MMM') : '—' }}</div>
                                    <div class="text-xs tabular-nums text-fg-subtle">{{ $b->hhmm() ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar :name="$b->clientName()" class="size-8 text-xs" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $b->clientName() }}</div>
                                            <div class="text-xs text-fg-subtle" dir="ltr">{{ $b->clientPhone() ?: '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-fg-muted">{{ $b->serviceName() }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $b->employeeName() ?: '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$b->status" :label="$b->statusEnum()->label()" /></td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-primary-600">{{ $b->price ? Money::format($b->price) : '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <a href="{{ route('bookings.detail', $b->uuid) }}" wire:navigate class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="calendar-check" class="size-4" />{{ __('dash.details') }}</a>
                                            @unless ($b->statusEnum()->isTerminal())
                                                <button wire:click="confirmCancel('{{ $b->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="x" class="size-4" />{{ __('dash.statusCancelled') }}</button>
                                            @endunless
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Cancel confirmation --}}
    <x-ui.confirm-dialog wire="showCancel" :title="__('bookings.cancelTitle')" :description="__('bookings.cancelDesc')" action="cancelBooking" :actionLabel="__('bookings.confirmCancel')">
        <textarea wire:model="cancelReason" rows="2" placeholder="{{ __('bookings.cancelReason') }}" class="mt-3 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none"></textarea>
    </x-ui.confirm-dialog>
</div>
