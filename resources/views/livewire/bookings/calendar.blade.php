@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $employees = $this->employees();
    $isToday = $date === Carbon::today()->toDateString();
@endphp

<div class="p-6">
    {{-- Header --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center rounded-lg border border-line bg-surface">
                <button wire:click="prev" class="grid size-9 place-items-center text-fg-muted hover:text-fg"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></button>
                <button wire:click="today" class="border-x border-line px-3 py-1.5 text-sm font-medium {{ $isToday ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">{{ __('dash.dateToday') }}</button>
                <button wire:click="next" class="grid size-9 place-items-center text-fg-muted hover:text-fg"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></button>
            </div>
            <h1 class="text-lg font-semibold text-fg">
                @switch($view)
                    @case('week')
                        {{ Carbon::parse($this->weekDays()[0])->isoFormat('D MMM') }} – {{ Carbon::parse($this->weekDays()[6])->isoFormat('D MMM YYYY') }}
                        @break
                    @case('month')
                        {{ Carbon::parse($date)->isoFormat('MMMM YYYY') }}
                        @break
                    @default
                        {{ Carbon::parse($date)->isoFormat('ddd, D MMM YYYY') }}
                @endswitch
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-lg border border-line bg-surface-2 p-0.5">
                @foreach (['day' => 'bookings.day', 'week' => 'bookings.week', 'month' => 'bookings.month'] as $v => $label)
                    <button wire:click="$set('view', '{{ $v }}')"
                        @class([
                            'rounded-md px-3 py-1.5 text-sm font-medium transition',
                            'bg-surface text-fg shadow-xs' => $view === $v,
                            'text-fg-muted hover:text-fg' => $view !== $v,
                        ])>{{ __($label) }}</button>
                @endforeach
            </div>
            <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none">
                <option value="all">{{ __('common.allStatuses') }}</option>
                @foreach (\App\Enums\BookingStatus::cases() as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="employeeFilter" aria-label="{{ $provider->terminology()['staff'] }}" class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none">
                <option value="all">{{ __('common.allStaff') }}</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->uuid }}">{{ $emp->name }}</option>
                @endforeach
            </select>
            <x-ui.button href="{{ route('bookings.new', ['date' => $date]) }}" wire:navigate icon="plus">{{ __('dash.newBooking') }}</x-ui.button>
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if ($view === 'day')
    @php
        $slots = $this->timeSlots();
        $blocks = $this->blocks();
        $queue = $this->queueMap();
        $summary = $this->summary();
        $slotPx = \App\Livewire\Bookings\Calendar::SLOT_PX;
        $gridHeight = count($slots) * $slotPx;
    @endphp

    {{-- Day summary chips --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-line bg-surface px-4 py-3"><p class="text-xs text-fg-muted">{{ __('dash.total') }}</p><p class="text-lg font-semibold text-fg">{{ $summary['total'] }}</p></div>
        <div class="rounded-xl border border-line bg-surface px-4 py-3"><p class="text-xs text-fg-muted">{{ __('dash.statusConfirmed') }}</p><p class="text-lg font-semibold text-info">{{ $summary['confirmed'] }}</p></div>
        <div class="rounded-xl border border-line bg-surface px-4 py-3"><p class="text-xs text-fg-muted">{{ __('dash.statusCompleted') }}</p><p class="text-lg font-semibold text-success">{{ $summary['completed'] }}</p></div>
        <div class="rounded-xl border border-line bg-surface px-4 py-3"><p class="text-xs text-fg-muted">{{ __('dash.colRevenue') }}</p><p class="text-lg font-semibold text-primary-600">{{ Money::compact($summary['revenue']) }}</p></div>
    </div>

    {{-- Calendar grid --}}
    <x-ui.card padding="p-0" class="overflow-hidden">
        @if (count($employees) === 0)
            <x-ui.empty-state :title="__('employees.noEmployeesFound')" :description="__('employees.noEmployeesDesc')" icon="user-cog" />
        @else
            <div class="flex">
                {{-- Time gutter --}}
                <div class="w-14 shrink-0 border-e border-line">
                    <div class="h-12 border-b border-line"></div>
                    <div class="relative" style="height: {{ $gridHeight }}px">
                        @foreach ($slots as $i => $time)
                            <div class="absolute w-full -translate-y-1/2 pe-2 text-end text-[11px] tabular-nums text-fg-subtle" style="top: {{ $i * $slotPx }}px">{{ $time }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- Employee columns (scroll on overflow) --}}
                <div class="flex-1 overflow-x-auto">
                    <div class="flex min-w-max">
                        @foreach ($employees as $empIndex => $emp)
                            <div class="min-w-[160px] flex-1 border-e border-line last:border-e-0">
                                {{-- Column header --}}
                                <div class="flex h-12 items-center gap-2 border-b border-line bg-surface-2/50 px-3">
                                    <x-ui.avatar :name="$emp->name" class="size-7 text-[11px]" />
                                    <span class="truncate text-sm font-medium text-fg">{{ $emp->name }}</span>
                                </div>
                                {{-- Column body --}}
                                <div class="relative" style="height: {{ $gridHeight }}px">
                                    @foreach ($slots as $i => $time)
                                        <a href="{{ route('bookings.new', ['date' => $date, 'time' => $time, 'emp' => $emp->uuid]) }}" wire:navigate
                                           class="group absolute inset-x-0 border-t border-line/50 transition-colors hover:bg-primary-50/50 dark:hover:bg-primary-900/10"
                                           style="top: {{ $i * $slotPx }}px; height: {{ $slotPx }}px">
                                            <span class="pointer-events-none absolute inset-0 hidden place-items-center text-primary-400 group-hover:grid"><x-icon name="plus" class="size-4" /></span>
                                        </a>
                                    @endforeach

                                    @foreach ($blocks as $block)
                                        @if ($block['empIndex'] === $empIndex)
                                            <a href="{{ route('bookings.detail', $block['uuid']) }}" wire:navigate wire:key="blk-{{ $block['uuid'] }}"
                                               class="absolute inset-x-1 z-10 overflow-hidden rounded-lg border-s-4 px-2 py-1.5 text-xs shadow-sm transition-shadow hover:shadow-md {{ $block['status'] === 'cancelled' ? 'line-through opacity-60' : '' }}"
                                               style="top: {{ $block['startSlot'] * $slotPx }}px; height: {{ $block['span'] * $slotPx - 4 }}px; border-inline-start-color: {{ $block['color'] }}; background-color: {{ $block['color'] }}1a">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-medium tabular-nums text-fg">{{ $block['start'] }}–{{ $block['end'] }}</span>
                                                    @if (isset($queue[$block['uuid']]))
                                                        <span class="grid size-4 place-items-center rounded-full text-[10px] font-bold text-white" style="background-color: {{ $block['color'] }}">{{ $queue[$block['uuid']] }}</span>
                                                    @endif
                                                </div>
                                                <div class="mt-0.5 truncate font-semibold text-fg">{{ $block['client'] }}</div>
                                                <div class="truncate text-fg-muted">{{ $block['service'] }}</div>
                                                @if ($block['span'] >= 2 && $block['price'])
                                                    <div class="mt-0.5 font-medium text-primary-600">{{ Money::format($block['price']) }}</div>
                                                @endif
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Week view: 7-column day list --}}
    @elseif ($view === 'week')
        @php $grouped = $this->rangeBookings(); $today = Carbon::today()->toDateString(); @endphp
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-7">
            @foreach ($this->weekDays() as $day)
                @php $dayBookings = $grouped[$day] ?? []; $isDayToday = $day === $today; @endphp
                <div wire:key="wk-{{ $day }}" class="flex flex-col rounded-xl border {{ $isDayToday ? 'border-primary-400' : 'border-line' }} bg-surface">
                    <div class="border-b border-line px-3 py-2 text-center {{ $isDayToday ? 'text-primary-600' : 'text-fg-muted' }}">
                        <p class="text-xs font-medium uppercase">{{ Carbon::parse($day)->isoFormat('ddd') }}</p>
                        <p class="text-lg font-bold {{ $isDayToday ? 'text-primary-600' : 'text-fg' }}">{{ Carbon::parse($day)->isoFormat('D') }}</p>
                    </div>
                    <div class="flex flex-1 flex-col gap-1.5 p-2">
                        @forelse ($dayBookings as $b)
                            <a href="{{ route('bookings.detail', $b->uuid) }}" wire:navigate wire:key="wkb-{{ $b->uuid }}"
                               class="block overflow-hidden rounded-lg border-s-4 bg-surface-2 px-2 py-1.5 text-xs transition hover:shadow-sm {{ $b->status === 'cancelled' ? 'line-through opacity-60' : '' }}"
                               style="border-inline-start-color: {{ $b->statusEnum()->color() }}">
                                <p class="font-semibold tabular-nums text-fg">{{ $b->hhmm() }}</p>
                                <p class="truncate text-fg-muted">{{ $b->serviceName() }}</p>
                                <p class="truncate text-fg-subtle">{{ $b->clientName() }}</p>
                            </a>
                        @empty
                            <a href="{{ route('bookings.new', ['date' => $day]) }}" wire:navigate class="grid flex-1 place-items-center rounded-lg border border-dashed border-line py-4 text-fg-subtle hover:border-primary-400 hover:text-primary-500">
                                <x-icon name="plus" class="size-4" />
                            </a>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

    {{-- Month view: 6×7 grid --}}
    @else
        @php $grouped = $this->rangeBookings(); @endphp
        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="grid grid-cols-7 border-b border-line bg-surface-2/50 text-center text-xs font-semibold uppercase text-fg-subtle">
                @foreach ([0, 1, 2, 3, 4, 5, 6] as $wd)
                    <div class="py-2">{{ Carbon::parse($this->weekDays()[$wd])->isoFormat('ddd') }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($this->monthCells() as $cell)
                    @php $dayBookings = $grouped[$cell['date']] ?? []; $extra = count($dayBookings) - 3; @endphp
                    <div wire:key="mo-{{ $cell['date'] }}"
                        class="min-h-24 border-b border-e border-line p-1.5 last:border-e-0 {{ $cell['inMonth'] ? 'bg-surface' : 'bg-surface-2/40' }}">
                        <a href="{{ route('bookings.new', ['date' => $cell['date']]) }}" wire:navigate
                            class="mb-1 grid size-6 place-items-center rounded-full text-xs font-semibold {{ $cell['isToday'] ? 'bg-primary-500 text-white' : ($cell['inMonth'] ? 'text-fg hover:bg-surface-2' : 'text-fg-subtle') }}">
                            {{ $cell['day'] }}
                        </a>
                        <div class="space-y-1">
                            @foreach (array_slice($dayBookings, 0, 3) as $b)
                                <a href="{{ route('bookings.detail', $b->uuid) }}" wire:navigate wire:key="mob-{{ $b->uuid }}"
                                   class="flex items-center gap-1 overflow-hidden rounded px-1 py-0.5 text-[11px] {{ $b->status === 'cancelled' ? 'line-through opacity-60' : '' }}"
                                   style="background-color: {{ $b->statusEnum()->color() }}22">
                                    <span class="size-1.5 shrink-0 rounded-full" style="background-color: {{ $b->statusEnum()->color() }}"></span>
                                    <span class="truncate tabular-nums text-fg">{{ $b->hhmm() }}</span>
                                    <span class="truncate text-fg-muted">{{ $b->serviceName() }}</span>
                                </a>
                            @endforeach
                            @if ($extra > 0)
                                <p class="px-1 text-[11px] font-medium text-fg-subtle">+{{ $extra }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</div>
