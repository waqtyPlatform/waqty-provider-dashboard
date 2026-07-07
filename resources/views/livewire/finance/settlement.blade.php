@php use App\Support\Money; use Illuminate\Support\Carbon; $s = $this->summary(); @endphp

<div class="p-6">
    <x-ui.page-header :title="__('settle.title')" :subtitle="__('settle.subtitle')" />

    {{-- Net payable hero --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:bg-primary-900/20 lg:col-span-1">
            <p class="text-sm text-primary-700 dark:text-primary-300">{{ __('settle.netThisPeriod') }}</p>
            <p class="mt-1 text-2xl font-bold text-primary-600">{{ Money::format($s['net']) }}</p>
        </div>
        <x-ui.kpi-card :label="__('settle.grossBookings')" :value="Money::compact($s['gross'])" icon="wallet" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('settle.commission')" :value="Money::compact($s['commission'])" icon="rotate-ccw" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('settle.fees')" :value="Money::compact($s['fees'])" icon="receipt" iconClass="bg-error-light text-error" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Payouts --}}
        <x-ui.card padding="p-0">
            <h2 class="border-b border-line px-5 py-4 font-semibold text-fg">{{ __('settle.payouts') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[460px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-5 py-3 text-start font-semibold">{{ __('settle.period') }}</th>
                            <th class="px-5 py-3 text-end font-semibold">{{ __('settle.gross') }}</th>
                            <th class="px-5 py-3 text-end font-semibold">{{ __('settle.netPayable') }}</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('settle.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->payouts() as $p)
                            <tr wire:key="payout-{{ $loop->index }}" class="border-b border-line last:border-0">
                                <td class="px-5 py-3 font-medium text-fg">{{ $p['period'] }}</td>
                                <td class="px-5 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($p['gross']) }}</td>
                                <td class="px-5 py-3 text-end font-medium tabular-nums text-primary-600">{{ Money::format($p['net']) }}</td>
                                <td class="px-5 py-3"><x-ui.status-pill :status="$p['status'] === 'paid' ? 'completed' : ($p['status'] === 'processing' ? 'pending' : 'cancelled')" :label="match($p['status']) { 'paid' => __('settle.statusPaid'), 'processing' => __('settle.statusProcessing'), 'failed' => __('settle.statusFailed'), default => __('settle.statusPending') }" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Commission ledger --}}
        <x-ui.card padding="p-0">
            <h2 class="border-b border-line px-5 py-4 font-semibold text-fg">{{ __('settle.ledger') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[460px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-5 py-3 text-start font-semibold">{{ __('settle.visit') }}</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('settle.date') }}</th>
                            <th class="px-5 py-3 text-end font-semibold">{{ __('settle.rate') }}</th>
                            <th class="px-5 py-3 text-end font-semibold">{{ __('settle.commissionCol') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->ledger() as $l)
                            <tr wire:key="ledger-{{ $loop->index }}" class="border-b border-line last:border-0">
                                <td class="px-5 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $l['visit'] }}</td>
                                <td class="px-5 py-3 text-fg-muted">{{ Carbon::parse($l['date'])->isoFormat('D MMM') }}</td>
                                <td class="px-5 py-3 text-end tabular-nums text-fg-muted">{{ $l['rate'] }}%</td>
                                <td class="px-5 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($l['commission']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
