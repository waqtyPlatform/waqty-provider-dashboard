<?php

declare(strict_types=1);

use App\Livewire\Reports\ReportDetail;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeReportTable(): void
{
    Http::fake([
        '*/api/provider/reports/*/*' => Http::response(['success' => true, 'data' => [
            ['name' => 'Alpha', 'revenue' => 500000, 'bookings' => 12],
            ['name' => 'Beta', 'revenue' => 900000, 'bookings' => 34],
        ]]),
    ]);
}

it('renders drill-down rows, KPI strip and table from the API', function () {
    fakeReportTable();

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertSee('5,000 EGP')   // Money::format(500000 minor)
        ->assertSee('9,000 EGP')   // Money::format(900000 minor)
        ->assertSee('14K EGP');    // total revenue KPI (compact)
});

it('falls back to Arabic sample rows when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->assertSee('sample data')
        ->assertSee('وسط البلد');
});

it('adapts the fallback names to the drill-down (employees)', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ReportDetail::class, ['category' => 'sales', 'report' => 'by-employee'])
        ->assertSee('sample data')
        ->assertSee('خالد حسن');
});

it('sorts the table when a column header is clicked', function () {
    fakeReportTable();

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->call('sort', 'revenue')                              // asc
        ->assertSeeInOrder(['5,000 EGP', '9,000 EGP'])
        ->call('sort', 'revenue')                              // toggles to desc
        ->assertSeeInOrder(['9,000 EGP', '5,000 EGP']);
});

it('filters the table rows by search', function () {
    fakeReportTable();

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->set('search', 'Alpha')
        ->assertSee('5,000 EGP')       // Alpha row kept
        ->assertDontSee('9,000 EGP');  // Beta row filtered out of the table
});

it('exports and notifies with a url', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->call('export', 'csv')
        ->assertDispatched('notify');
});

it('dispatches a chart refresh when the range changes', function () {
    fakeReportTable();

    Livewire::test(ReportDetail::class, ['category' => 'revenue', 'report' => 'by-branch'])
        ->set('range', '6m')
        ->assertDispatched('report-detail-charts');
});
