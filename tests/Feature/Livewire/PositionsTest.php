<?php

declare(strict_types=1);

use App\Livewire\Employees\Positions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePositions(): void
{
    Http::fake([
        '*/api/provider/positions*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'P1', 'title' => 'Senior Stylist', 'department' => 'Styling', 'level' => 'senior', 'salary_min' => 400000, 'salary_max' => 800000],
            ['uuid' => 'P2', 'title' => 'Nail Technician', 'department' => 'Nails', 'level' => 'junior', 'salary_min' => 250000, 'salary_max' => 400000],
        ]]),
    ]);
}

it('renders positions from the API', function () {
    fakePositions();

    Livewire::test(Positions::class)
        ->assertSee('Senior Stylist')
        ->assertSee('Nail Technician')
        ->assertSee('Styling');
});

it('falls back to Arabic sample positions when the API is down', function () {
    Http::fake(['*/api/provider/positions*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Positions::class)
        ->assertSee('sample data')
        ->assertSee('مصفف شعر أول');
});

it('validates the create form', function () {
    fakePositions();

    Livewire::test(Positions::class)
        ->call('openCreate')
        ->set('form_title', '')
        ->set('form_salary_min', '5000')
        ->set('form_salary_max', '3000')
        ->call('save')
        ->assertHasErrors(['form_title' => 'required', 'form_salary_max' => 'gte'])
        ->assertSet('showForm', true);
});

it('creates a position and notifies', function () {
    fakePositions();

    Livewire::test(Positions::class)
        ->call('openCreate')
        ->set('form_title', 'Spa Therapist')
        ->set('form_department', 'Spa')
        ->set('form_level', 'mid')
        ->set('form_salary_min', '3000')
        ->set('form_salary_max', '6000')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/positions')
        && $req['title'] === 'Spa Therapist'
        && $req['salary_min'] === 300000
        && $req['salary_max'] === 600000);
});
