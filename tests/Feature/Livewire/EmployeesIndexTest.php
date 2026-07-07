<?php

declare(strict_types=1);

use App\Livewire\Employees\Index;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeEmployees(): void
{
    Http::fake([
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed', 'email' => 'sara@x.com', 'phone' => '01000000000', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'Downtown'], 'active' => true, 'blocked' => false, 'role' => 'admin'],
            ['uuid' => 'E2', 'name' => 'Khaled Hassan', 'email' => 'khaled@x.com', 'phone' => '01111111111', 'branch_uuid' => 'B2', 'branch' => ['uuid' => 'B2', 'name' => 'New Cairo'], 'active' => false, 'blocked' => false, 'role' => 'staff'],
        ]]),
    ]);
}

it('lists employees from the API', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Downtown');
});

it('filters employees by search', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->set('search', 'sara')
        ->assertSee('Sara Ahmed')
        ->assertDontSee('Khaled Hassan');
});

it('filters employees by status', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('Khaled Hassan')
        ->assertDontSee('Sara Ahmed');
});

it('toggles employee active state via the API', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->call('toggleActive', 'E1')
        ->assertSet('overrides.E1.active', false);

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/employees/E1/active')
        && $req['active'] === false);
});

it('validates the create form', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_password', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required', 'form_password' => 'required'])
        ->assertSet('showForm', true);
});

it('creates an employee and closes the slide-over', function () {
    fakeEmployees();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', 'Mona Adel')
        ->set('form_email', 'mona@x.com')
        ->set('form_phone', '01234567890')
        ->set('form_password', 'secret123')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/employees')
        && $req['name'] === 'Mona Adel');
});

it('falls back to sample data when the API is unavailable', function () {
    Http::fake(['*/api/provider/employees*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Index::class)
        ->assertSee('د. سارة أحمد') // fallback sample
        ->assertSee('sample data');
});
