@php
    use App\Support\Money;
    use App\Enums\UserRole;
    use Illuminate\Support\Carbon;

    $canManage = $provider->role() !== UserRole::Staff;
    $variance = (int) $this->kpis['variance'];
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'shifts'" />

    <x-ui.page-header :title="__('txn.shifts.title')" :subtitle="__('txn.shifts.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.shifts.kpiOpen')" :value="$this->kpis['open']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('txn.shifts.kpiVariance')" :value="Money::compact($variance)" icon="gauge" iconClass="{{ $variance < 0 ? 'bg-error-light text-error' : 'bg-info-light text-info' }}" />
        <x-ui.kpi-card :label="__('txn.shifts.kpiToday')" :value="$this->kpis['today']" icon="calendar-check" iconClass="bg-primary-50 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="statusFilter" aria-label="{{ __('txn.shifts.filterStatus') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('txn.shifts.statusAll') }}</option>
            <option value="open">{{ __('txn.shifts.statusOpen') }}</option>
            <option value="closed">{{ __('txn.shifts.statusClosed') }}</option>
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.shifts.empty')" icon="clock" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.shifts.thShift') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.shifts.thCashier') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.shifts.thOpened') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.shifts.thClosed') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.shifts.thExpected') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.shifts.thActual') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.shifts.thVariance') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            @if ($canManage)<th class="px-4 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $s)
                            @php
                                $isOpen = ($s['status'] ?? '') === 'open';
                                $var = (int) ($s['variance'] ?? 0);
                            @endphp
                            <tr wire:key="shift-{{ $s['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $s['label'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $s['cashier'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ isset($s['opened_at']) ? Carbon::parse($s['opened_at'])->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ ! empty($s['closed_at']) ? Carbon::parse($s['closed_at'])->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg">{{ Money::format((int) ($s['expected_total'] ?? 0)) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums {{ $isOpen ? 'text-fg-subtle' : 'text-fg' }}">{{ $isOpen ? '—' : Money::format((int) ($s['actual_total'] ?? 0)) }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums {{ $isOpen ? 'text-fg-subtle' : ($var < 0 ? 'text-error' : ($var > 0 ? 'text-success' : 'text-fg-muted')) }}">
                                    {{ $isOpen ? '—' : ($var > 0 ? '+' : '').Money::format($var) }}
                                </td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$isOpen ? 'pending' : 'completed'" :label="$isOpen ? __('txn.shifts.statusOpen') : __('txn.shifts.statusClosed')" /></td>
                                @if ($canManage)
                                    <td class="px-4 py-3 text-end">
                                        @if ($isOpen)
                                            <x-ui.button size="sm" variant="secondary" icon="check-circle-2" wire:click="openClose('{{ $s['uuid'] }}')">{{ __('txn.shifts.close') }}</x-ui.button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    @if ($canManage)
        <x-ui.confirm-dialog wire="showClose" :title="__('txn.shifts.closeTitle')" :description="__('txn.shifts.closeDesc')" action="close" :actionLabel="__('txn.shifts.closeConfirm')" variant="primary" icon="clock" />
    @endif
</div>
