<?php

declare(strict_types=1);

use App\Livewire\Employees\BranchManagement;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeBranches(): void
{
    Http::fake([
        '*/api/provider/branches*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'B1', 'name' => 'Downtown', 'area' => 'Cairo', 'staff' => [
                ['name' => 'Sara Ahmed', 'position' => 'Branch Manager', 'active' => true],
                ['name' => 'Khaled Hassan', 'position' => 'Stylist', 'active' => true],
            ]],
            ['uuid' => 'B2', 'name' => 'New Cairo', 'area' => 'Cairo', 'staff' => [
                ['name' => 'Yasmin Farouk', 'position' => 'Receptionist', 'active' => false],
            ]],
        ]]),
    ]);
}

it('renders branch rosters from the API with staff names and positions', function () {
    fakeBranches();

    Livewire::test(BranchManagement::class)
        ->assertSee('Downtown')
        ->assertSee('New Cairo')
        ->assertSee('Sara Ahmed')
        ->assertSee('Branch Manager');
});

it('computes headcount and average staff KPIs', function () {
    fakeBranches();

    $kpis = Livewire::test(BranchManagement::class)->instance()->kpis();

    expect($kpis['branches'])->toBe(2)
        ->and($kpis['staff'])->toBe(3)
        ->and($kpis['avg'])->toBe(1.5); // 3 staff / 2 branches
});

it('falls back to Arabic sample branches when the API is unavailable', function () {
    Http::fake(['*/api/provider/branches*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(BranchManagement::class)
        ->assertSee('sample data')
        ->assertSee('فرع وسط البلد')
        ->assertSee('ياسمين فاروق');
});
