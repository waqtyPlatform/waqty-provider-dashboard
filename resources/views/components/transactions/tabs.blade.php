@props(['active' => 'log'])

@php
    use App\Support\RouteAccess;

    $role = $provider->role()->value;
    $tabs = [
        ['key' => 'log', 'label' => 'txn.tabLog', 'href' => '/transactions'],
        ['key' => 'cash-sales', 'label' => 'txn.tabCashSales', 'href' => '/transactions/cash-sales'],
        ['key' => 'client-sales', 'label' => 'txn.tabClientSales', 'href' => '/transactions/client-sales'],
        ['key' => 'advance-payments', 'label' => 'txn.tabAdvance', 'href' => '/transactions/advance-payments'],
        ['key' => 'petty-cash', 'label' => 'txn.tabPettyCash', 'href' => '/transactions/petty-cash'],
        ['key' => 'transfers', 'label' => 'txn.tabTransfers', 'href' => '/transactions/transfers'],
        ['key' => 'safe-balances', 'label' => 'txn.tabSafeBalances', 'href' => '/transactions/safe-balances'],
        ['key' => 'shifts', 'label' => 'txn.tabShifts', 'href' => '/transactions/shifts'],
        ['key' => 'dailies', 'label' => 'txn.tabDailies', 'href' => '/transactions/dailies'],
        ['key' => 'best-sales', 'label' => 'txn.tabBestSales', 'href' => '/transactions/best-sales'],
        ['key' => 'package-sales', 'label' => 'txn.tabPackageSales', 'href' => '/transactions/package-sales'],
    ];
    $tabs = array_values(array_filter($tabs, fn ($t) => RouteAccess::allowed($t['href'], $role)));
@endphp

<div class="mb-5 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
    <div class="flex min-w-max gap-1 border-b border-line">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}" wire:navigate
                @class([
                    'whitespace-nowrap border-b-2 px-3.5 py-2.5 text-sm font-medium transition',
                    'border-primary-500 text-primary-600' => $active === $tab['key'],
                    'border-transparent text-fg-muted hover:text-fg' => $active !== $tab['key'],
                ])>
                {{ __($tab['label']) }}
            </a>
        @endforeach
    </div>
</div>
