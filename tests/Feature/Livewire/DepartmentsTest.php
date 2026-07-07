<?php

declare(strict_types=1);

use App\Livewire\Employees\Departments;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists departments from the API', function () {
    Http::fake(['*/api/provider/departments' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'D1', 'name' => 'قسم الاستقبال', 'description' => 'استقبال العملاء وإدارة المواعيد', 'employees_count' => 12],
    ]])]);

    Livewire::test(Departments::class)
        ->assertOk()
        ->assertSee('قسم الاستقبال')
        ->assertSee('استقبال العملاء وإدارة المواعيد')
        ->assertSee('12');
});

it('falls back to Arabic sample departments when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Departments::class)
        ->assertSee('sample data')
        ->assertSee('الاستقبال')
        ->assertSee('التصفيف')
        ->assertSee('الإدارة');
});

it('requires a name when saving', function () {
    Http::fake(['*/api/provider/departments' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Departments::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required'])
        ->assertSet('showForm', true);
});

it('creates a department and notifies', function () {
    Http::fake(['*/api/provider/departments' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Departments::class)
        ->call('openCreate')
        ->set('form_name', 'قسم التسويق')
        ->set('form_description', 'إدارة الحملات والعروض')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/departments')
        && $req['name'] === 'قسم التسويق');
});
