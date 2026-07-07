@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
@endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'safe-balances'" />

    <x-ui.page-header :title="__('txn.safebalances.title')" :subtitle="__('txn.safebalances.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.kpi-card :label="__('txn.safebalances.totalBalance')" :value="Money::format($this->kpis['total'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('txn.safebalances.safeCount')" :value="$this->kpis['count']" icon="shield" iconClass="bg-info-light text-info" />
    </div>

    @if (count($this->safes) === 0)
        <x-ui.empty-state :title="__('common.noData')" icon="wallet" />
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->safes as $safe)
                @php $active = (bool) ($safe['is_active'] ?? false); @endphp
                <x-ui.card wire:key="safe-{{ $safe['uuid'] ?? $loop->index }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold text-fg">{{ $safe['name'] ?? '—' }}</h3>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm text-fg-muted">
                                <x-icon name="building-2" class="size-3.5 shrink-0" />
                                <span class="truncate">{{ $safe['branch'] ?? '—' }}</span>
                            </p>
                        </div>
                        <x-ui.badge :color="$active ? 'success' : 'neutral'">
                            {{ $active ? __('txn.safebalances.active') : __('txn.safebalances.inactive') }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-4">
                        <p class="text-2xl font-semibold tabular-nums text-fg">{{ Money::format((int) ($safe['balance'] ?? 0)) }}</p>
                        <p class="mt-0.5 text-xs text-fg-subtle">{{ __('txn.safebalances.currentBalance') }}</p>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-line pt-3 text-xs text-fg-muted">
                        <x-icon name="clock" class="size-3.5 shrink-0" />
                        <span>{{ __('txn.safebalances.lastActivity') }}:</span>
                        <span class="text-fg">{{ isset($safe['last_activity']) ? Carbon::parse($safe['last_activity'])->isoFormat('D MMM، HH:mm') : '—' }}</span>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
