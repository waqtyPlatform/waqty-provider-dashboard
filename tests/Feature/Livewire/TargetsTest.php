<?php

declare(strict_types=1);

use App\Livewire\Employees\Targets;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeTargets(): void
{
    Http::fake([
        '*/api/provider/employee-targets*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'T1', 'employee' => 'Sara Ahmed', 'type' => 'revenue', 'target' => 30000000, 'achieved' => 34500000, 'tier_multiplier' => 1.5, 'bonus' => 500000, 'period' => 'Jul 2026'],
            ['uuid' => 'T2', 'employee' => 'Khaled Hassan', 'type' => 'bookings', 'target' => 120, 'achieved' => 92, 'tier_multiplier' => 1.25, 'bonus' => 250000, 'period' => 'Jul 2026'],
        ]]),
    ]);
}

it('renders sales targets from the API with computed progress', function () {
    fakeTargets();

    Livewire::test(Targets::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('115%'); // 34,500,000 / 30,000,000
});

it('falls back to Arabic sample targets when the API is unavailable', function () {
    Http::fake(['*/api/provider/employee-targets*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Targets::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('عمر نبيل');
});

it('validates the target form', function () {
    fakeTargets();

    Livewire::test(Targets::class)
        ->call('openCreate')
        ->set('form_employee', '')
        ->set('form_value', '0')
        ->call('save')
        ->assertHasErrors(['form_employee' => 'required', 'form_value' => 'gt'])
        ->assertSet('showForm', true);
});

it('creates a target, notifies, and sends money as minor units', function () {
    fakeTargets();

    Livewire::test(Targets::class)
        ->call('openCreate')
        ->set('form_employee', 'Mona Adel')
        ->set('form_type', 'revenue')
        ->set('form_value', '250000')
        ->set('form_tier', '1.25')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/employee-targets')
        && $req['employee'] === 'Mona Adel'
        && $req['target'] === 25000000);
});
