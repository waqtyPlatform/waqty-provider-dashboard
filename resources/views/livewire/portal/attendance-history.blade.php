<div class="p-4 sm:p-6">
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-fg">{{ __('portal.attTitle') }}</h1>
        <div class="flex items-center gap-1">
            <button wire:click="prevMonth" class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2"><x-icon name="chevron-left" class="size-4 rtl:rotate-180" /></button>
            <span class="min-w-32 text-center text-sm font-medium text-fg">{{ $this->monthLabel() }}</span>
            <button wire:click="nextMonth" class="grid size-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2"><x-icon name="chevron-right" class="size-4 rtl:rotate-180" /></button>
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @php($stats = $this->stats())
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-line bg-surface p-4"><p class="text-2xl font-bold text-success">{{ $stats['present'] }}</p><p class="text-xs text-fg-muted">{{ __('portal.present') }}</p></div>
        <div class="rounded-xl border border-line bg-surface p-4"><p class="text-2xl font-bold text-warning">{{ $stats['late'] }}</p><p class="text-xs text-fg-muted">{{ __('portal.late') }}</p></div>
        <div class="rounded-xl border border-line bg-surface p-4"><p class="text-2xl font-bold text-error">{{ $stats['absent'] }}</p><p class="text-xs text-fg-muted">{{ __('portal.absent') }}</p></div>
        <div class="rounded-xl border border-line bg-surface p-4"><p class="text-2xl font-bold text-fg tabular-nums">{{ $stats['hours'] }}</p><p class="text-xs text-fg-muted">{{ __('portal.hours') }}</p></div>
    </div>

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('portal.colDate') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('portal.colStatus') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('portal.colCheckIn') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('portal.colCheckOut') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr wire:key="att-{{ $r['date'] ?? $loop->index }}" class="border-b border-line last:border-0">
                            <td class="px-4 py-3 font-medium text-fg">{{ $r['date'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php($st = $r['status'] ?? 'present')
                                <x-ui.badge :color="$st === 'present' ? 'success' : ($st === 'late' ? 'warning' : 'error')">{{ __('portal.'.$st) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-fg-muted">{{ $r['check_in'] ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-fg-muted">{{ $r['check_out'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-fg-subtle">{{ __('portal.noRecords') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
