<div class="p-4 sm:p-6">
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-fg">{{ __('portal.shiftsTitle') }}</h1>
        <div class="flex items-center gap-1">
            <button wire:click="prevMonth" class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></button>
            <span class="min-w-32 text-center text-sm font-medium text-fg">{{ $this->monthLabel() }}</span>
            <button wire:click="nextMonth" class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></button>
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if ($shifts === [])
        <div class="rounded-xl border border-dashed border-line py-14 text-center text-sm text-fg-subtle">{{ __('portal.noShifts') }}</div>
    @else
        <div class="space-y-2">
            @foreach ($shifts as $s)
                <div wire:key="sh-{{ $s['date'] ?? $loop->index }}" class="flex items-center gap-4 rounded-xl border border-line bg-surface p-4">
                    <div class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600">
                        <x-icon name="calendar-days" class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-fg">{{ $s['date'] ?? '—' }}</p>
                        <p class="text-sm text-fg-muted">{{ $s['branch'] ?? '' }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-sm font-semibold tabular-nums text-fg">{{ $s['start_time'] ?? '' }} – {{ $s['end_time'] ?? '' }}</p>
                        @if (! empty($s['label']))
                            <x-ui.badge color="neutral">{{ $s['label'] }}</x-ui.badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
