<?php

declare(strict_types=1);

use App\Livewire\Reports\ReportCategory;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeReportsDown(): void
{
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);
}

it('renders the revenue category hub with KPIs, chart title and sub-report links', function () {
    fakeReportsDown();

    Livewire::test(ReportCategory::class, ['category' => 'revenue'])
        ->assertSee('Revenue Overview')          // reports.cat.revenue.title
        ->assertSee('68K EGP')                    // Money::compact(6_760_000) total revenue
        ->assertSee('Monthly Revenue Trend')      // reports.chart.monthlyRevenueTrend
        ->assertSee('Daily Revenue')              // sub-report link
        ->assertSee('/reports/revenue/tax-report', false);
});

it('shows the Arabic sample banner and sample data when the API is down', function () {
    fakeReportsDown();

    Livewire::test(ReportCategory::class, ['category' => 'employees'])
        ->assertSee('sample data')                // common.sampleData (demo banner)
        ->assertSee('Staff Performance')          // reports.cat.employees.title
        ->assertSee('خالد حسن');                  // Arabic fallback employee name
});

it('builds a money bar chart for services with horizontal bars', function () {
    fakeReportsDown();

    $chart = Livewire::test(ReportCategory::class, ['category' => 'services'])
        ->instance()
        ->chartOptions();

    expect($chart['chart']['type'])->toBe('bar')
        ->and($chart['plotOptions']['bar']['horizontal'])->toBeTrue()
        ->and($chart['series'][0]['data'][0])->toBe(14500.0); // 1_450_000 minor → EGP
});

it('keeps count categories as raw integers in the chart', function () {
    fakeReportsDown();

    $chart = Livewire::test(ReportCategory::class, ['category' => 'bookings'])
        ->instance()
        ->chartOptions();

    expect($chart['plotOptions']['bar']['horizontal'])->toBeFalse()
        ->and($chart['series'][0]['data'])->toBe([58, 72, 65, 84, 79, 91]);
});

it('dispatches a chart refresh when the date range changes', function () {
    fakeReportsDown();

    Livewire::test(ReportCategory::class, ['category' => 'clients'])
        ->set('range', '6m')
        ->assertDispatched('reports-charts');
});

it('aborts with 404 for an unknown category', function () {
    fakeReportsDown();

    Livewire::test(ReportCategory::class, ['category' => 'nope'])
        ->assertStatus(404);
});
