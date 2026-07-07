@php
    use App\Support\Money;
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'best-sales'" />

    <x-ui.page-header :title="__('txn.bestsales.title')" :subtitle="__('txn.bestsales.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 inline-flex rounded-lg border border-line bg-surface p-1">
        <button type="button" wire:click="setView('service')"
            @class([
                'inline-flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-sm font-medium transition',
                'bg-primary-500 text-white' => $view === 'service',
                'text-fg-muted hover:text-fg' => $view !== 'service',
            ])>
            <x-icon name="scissors" class="size-4" />{{ __('txn.bestsales.byService') }}
        </button>
        <button type="button" wire:click="setView('employee')"
            @class([
                'inline-flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-sm font-medium transition',
                'bg-primary-500 text-white' => $view === 'employee',
                'text-fg-muted hover:text-fg' => $view !== 'employee',
            ])>
            <x-icon name="users" class="size-4" />{{ __('txn.bestsales.byEmployee') }}
        </button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.bestsales.topItem')" :value="$this->kpis['top']" icon="star" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('txn.bestsales.totalRevenue')" :value="Money::compact($this->kpis['revenue'])" icon="trending-up" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('txn.bestsales.itemsSold')" :value="number_format($this->kpis['count'])" icon="shopping-bag" iconClass="bg-info-light text-info" />
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->ranked) === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="star" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="w-16 px-4 py-3 text-start font-semibold">{{ __('txn.bestsales.rank') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $view === 'employee' ? __('txn.bestsales.employee') : __('txn.bestsales.service') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.bestsales.qty') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.bestsales.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->ranked as $i => $row)
                            @php
                                $rank = $i + 1;
                                $pct = max(4, (int) round($row['revenue'] / $this->maxRevenue * 100));
                                $rankCls = match ($rank) {
                                    1 => 'bg-primary-500 text-white',
                                    2 => 'bg-primary-100 text-primary-700',
                                    3 => 'bg-warning-light text-warning',
                                    default => 'bg-surface-3 text-fg-muted',
                                };
                            @endphp
                            <tr wire:key="bs-{{ $view }}-{{ $i }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <span class="grid size-7 place-items-center rounded-full text-xs font-bold tabular-nums {{ $rankCls }}">{{ $rank }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-fg">{{ $row['name'] }}</p>
                                    <div class="mt-1.5 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-surface-3">
                                        <div class="h-full rounded-full bg-primary-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ number_format($row['count']) }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($row['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
