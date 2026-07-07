@php
    use Illuminate\Support\Carbon;
    $slots = $this->timeSlots();
    $rooms = $this->rooms();
    $blocks = $this->blocks();
    $visibleRooms = $this->visibleRooms();
    $slotPx = \App\Livewire\Bookings\Rooms::SLOT_PX;
    $gridHeight = count($slots) * $slotPx;
    $isToday = $date === Carbon::today()->toDateString();
@endphp

<div class="p-6">
    {{-- Header --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center rounded-lg border border-line bg-surface">
                <button wire:click="prevDay" class="grid size-9 place-items-center text-fg-muted hover:text-fg"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></button>
                <button wire:click="today" class="border-x border-line px-3 py-1.5 text-sm font-medium {{ $isToday ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">{{ __('dash.dateToday') }}</button>
                <button wire:click="nextDay" class="grid size-9 place-items-center text-fg-muted hover:text-fg"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></button>
            </div>
            <h1 class="text-lg font-semibold text-fg">{{ Carbon::parse($date)->isoFormat('ddd, D MMM YYYY') }}</h1>
        </div>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-fg-muted">
            <span>{{ __('bookings.busyOnly') }}</span>
            <x-ui.toggle :on="$busyOnly" wire:click="$toggle('busyOnly')" size="sm" />
        </label>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Rooms grid --}}
    <x-ui.card padding="p-0" class="overflow-hidden">
        @if (count($visibleRooms) === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="calendar-days" />
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

                {{-- Room columns (scroll on overflow) --}}
                <div class="flex-1 overflow-x-auto">
                    <div class="flex min-w-max">
                        @foreach ($visibleRooms as $roomIndex)
                            <div class="min-w-[160px] flex-1 border-e border-line last:border-e-0" wire:key="room-{{ $roomIndex }}">
                                {{-- Column header --}}
                                <div class="flex h-12 items-center gap-2 border-b border-line bg-surface-2/50 px-3">
                                    <span class="grid size-7 place-items-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-900/20"><x-icon name="building-2" class="size-4" /></span>
                                    <span class="truncate text-sm font-medium text-fg">{{ $rooms[$roomIndex] }}</span>
                                </div>
                                {{-- Column body --}}
                                <div class="relative" style="height: {{ $gridHeight }}px">
                                    @foreach ($slots as $i => $time)
                                        <div class="absolute inset-x-0 border-t border-line/50" style="top: {{ $i * $slotPx }}px; height: {{ $slotPx }}px"></div>
                                    @endforeach

                                    @foreach ($blocks as $block)
                                        @if ($block['roomIndex'] === $roomIndex)
                                            <a href="{{ route('bookings.detail', $block['uuid']) }}" wire:navigate wire:key="blk-{{ $block['uuid'] }}"
                                               class="absolute inset-x-1 z-10 overflow-hidden rounded-lg border-s-4 px-2 py-1.5 text-xs shadow-sm transition-shadow hover:shadow-md {{ $block['status'] === 'cancelled' ? 'line-through opacity-60' : '' }}"
                                               style="top: {{ $block['startSlot'] * $slotPx }}px; height: {{ $block['span'] * $slotPx - 4 }}px; border-inline-start-color: {{ $block['color'] }}; background-color: {{ $block['color'] }}1a">
                                                <div class="font-medium tabular-nums text-fg">{{ $block['start'] }}–{{ $block['end'] }}</div>
                                                <div class="mt-0.5 truncate font-semibold text-fg">{{ $block['client'] }}</div>
                                                <div class="truncate text-fg-muted">{{ $block['service'] }}</div>
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
</div>
