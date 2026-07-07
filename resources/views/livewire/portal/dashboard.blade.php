<div class="p-4 sm:p-6">
    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Check-in card --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line bg-surface p-5">
        <div class="flex items-center gap-3">
            <div @class(['grid size-12 place-items-center rounded-xl', 'bg-success/10 text-success' => $checkedIn, 'bg-surface-3 text-fg-subtle' => ! $checkedIn])>
                <x-icon name="clock" class="size-6" />
            </div>
            <div>
                @if ($checkedIn)
                    <p class="font-semibold text-fg">{{ __('portal.onDuty') }}</p>
                    <p class="text-sm text-fg-muted">{{ __('portal.checkedInSince') }} {{ $checkInTime ?? '—' }}</p>
                @else
                    <p class="font-semibold text-fg">{{ __('portal.notCheckedIn') }}</p>
                    <p class="text-sm text-fg-muted">{{ __('portal.checkInPrompt') }}</p>
                @endif
            </div>
        </div>
        @if ($checkedIn)
            <x-ui.button variant="destructive" icon="log-out" wire:click="checkOut">{{ __('portal.checkOut') }}</x-ui.button>
        @else
            <x-ui.button icon="check" wire:click="checkIn">{{ __('portal.checkIn') }}</x-ui.button>
        @endif
    </div>

    {{-- Stats --}}
    @php($stats = $this->stats())
    <div class="mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-xl border border-line bg-surface p-4 text-center">
            <p class="text-2xl font-bold text-fg">{{ $stats['total'] }}</p>
            <p class="text-xs text-fg-muted">{{ __('portal.statTotal') }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 text-center">
            <p class="text-2xl font-bold text-success">{{ $stats['done'] }}</p>
            <p class="text-xs text-fg-muted">{{ __('portal.statDone') }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4 text-center">
            <p class="text-2xl font-bold text-primary-600">{{ $stats['upcoming'] }}</p>
            <p class="text-xs text-fg-muted">{{ __('portal.statUpcoming') }}</p>
        </div>
    </div>

    {{-- Today's bookings --}}
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-fg-subtle">{{ __('portal.today') }}</h2>
    @if ($bookings === [])
        <div class="rounded-xl border border-dashed border-line py-12 text-center text-sm text-fg-subtle">{{ __('portal.noBookings') }}</div>
    @else
        <div class="space-y-2">
            @foreach ($bookings as $b)
                <div wire:key="pb-{{ $b['uuid'] ?? $loop->index }}" class="flex items-center gap-4 rounded-xl border border-line bg-surface p-3.5">
                    <div class="w-14 shrink-0 text-center">
                        <p class="text-sm font-bold tabular-nums text-fg">{{ $b['start_time'] ?? '—' }}</p>
                    </div>
                    <div class="min-w-0 flex-1 border-s border-line ps-4">
                        <p class="truncate font-medium text-fg">{{ $b['service'] ?? '—' }}</p>
                        <p class="truncate text-sm text-fg-muted">{{ $b['client'] ?? '—' }}</p>
                    </div>
                    @php($st = $b['status'] ?? 'pending')
                    <x-ui.status-pill :status="$st" :label="\App\Enums\BookingStatus::tryFrom($st)?->label()" />
                </div>
            @endforeach
        </div>
    @endif
</div>
