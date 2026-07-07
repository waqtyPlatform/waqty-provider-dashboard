<?php

declare(strict_types=1);

use App\Livewire\Employees\Roles;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists roles from the API', function () {
    Http::fake(['*/api/provider/roles' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'R1', 'name' => 'دور الاستقبال', 'members' => 5, 'system' => false, 'permissions' => [
            'bookings' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
        ]],
    ]])]);

    Livewire::test(Roles::class)
        ->assertOk()
        ->assertSee('دور الاستقبال')
        ->assertSee('5');
});

it('falls back to Arabic sample roles when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Roles::class)
        ->assertSee('sample data')
        ->assertSee('مدير')
        ->assertSee('مشرف')
        ->assertSee('موظف')
        ->assertSee('محاسب');
});

it('requires a name when saving', function () {
    Http::fake(['*/api/provider/roles' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Roles::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required'])
        ->assertSet('showForm', true);
});

it('creates a role with a permission matrix and notifies', function () {
    Http::fake(['*/api/provider/roles' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Roles::class)
        ->call('openCreate')
        ->set('form_name', 'أمين الصندوق')
        ->call('setLevel', 'transactions', 'full')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/roles')
        && $req['name'] === 'أمين الصندوق'
        && $req['permissions']['transactions']['view'] === true
        && $req['permissions']['transactions']['delete'] === true);
});
