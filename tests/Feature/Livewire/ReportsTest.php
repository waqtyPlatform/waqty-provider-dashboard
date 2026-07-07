<?php

declare(strict_types=1);

use App\Livewire\Reports\Overview;
use App\Livewire\Reports\Revenue;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeReports(): void
{
    Http::fake([
        '*/api/provider/reports/revenue*' => Http::response(['success' => true, 'data' => [
            'labels' => ['W1', 'W2', 'W3'],
            'datasets' => [
                ['label' => 'Revenue', 'data' => [500000, 620000, 580000]],
                ['label' => 'Expenses', 'data' => [200000, 210000, 190000]],
            ],
            'summary' => ['revenue' => 1700000, 'bookings' => 120, 'active_clients' => 64, 'growth' => 8.5],
        ]]),
        '*/api/provider/revenue*' => Http::response(['success' => true, 'data' => [
            'total_revenue' => 1700000,
            'by_branch' => [['branch_name' => 'Downtown', 'revenue' => 1000000], ['branch_name' => 'New Cairo', 'revenue' => 700000]],
            'by_employee' => [['employee_name' => 'Khaled Hassan', 'revenue' => 600000]],
        ]]),
    ]);
}

it('renders the reports overview with KPIs from the summary', function () {
    fakeReports();

    Livewire::test(Overview::class)
        ->assertSee('17K EGP')  // Money::compact(1_700_000)
        ->assertSee('120');     // bookings
});

it('builds report chart options from the API series', function () {
    fakeReports();

    $c = Livewire::test(Overview::class)->instance();
    $line = $c->revenueLineOptions();
    $branch = $c->branchBarOptions();

    expect($line['series'])->toHaveCount(2)
        ->and($line['series'][0]['data'])->toBe([5000.0, 6200.0, 5800.0]) // minor → EGP
        ->and($branch['series'][0]['data'])->toBe([10000.0, 7000.0]);
});

it('dispatches a chart-refresh event when the reports range changes', function () {
    fakeReports();

    Livewire::test(Overview::class)
        ->set('range', '6m')
        ->assertDispatched('reports-charts');
});

it('renders the revenue report with total + by-employee breakdown', function () {
    fakeReports();

    Livewire::test(Revenue::class)
        ->assertSee('17,000 EGP') // total revenue
        ->assertSee('Khaled Hassan');
});

it('falls back to sample report data when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Overview::class)->assertSee('sample data');
    Livewire::test(Revenue::class)
        ->assertSee('sample data')
        ->assertSee('وسط البلد');
});
