<?php

declare(strict_types=1);

use App\Livewire\Customers\Index;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeCustomers(): void
{
    Http::fake([
        '*/api/provider/customers*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'C1', 'name' => 'آية نبيل', 'phone' => '01000000000', 'email' => 'alice@x.com', 'group' => ['name' => 'VIP'], 'vip' => true, 'total_spent' => 150000, 'total_visits' => 3, 'last_visit' => '2026-01-01'],
            ['uuid' => 'C2', 'name' => 'باسم سمير', 'phone' => '01111111111', 'group' => ['name' => 'عادي'], 'vip' => false, 'total_spent' => 0, 'total_visits' => 0, 'last_visit' => null],
        ]]),
    ]);
}

it('lists customers from the API', function () {
    fakeCustomers();

    Livewire::test(Index::class)
        ->assertSee('آية نبيل')
        ->assertSee('باسم سمير')
        ->assertSee('1,500 EGP'); // 150000 minor units formatted
});

it('filters customers by search', function () {
    fakeCustomers();

    Livewire::test(Index::class)
        ->set('search', 'alice')
        ->assertSee('آية نبيل')
        ->assertDontSee('باسم سمير');
});

it('filters customers by group', function () {
    fakeCustomers();

    Livewire::test(Index::class)
        ->set('groupFilter', 'vip')
        ->assertSee('آية نبيل')
        ->assertDontSee('باسم سمير');
});

it('validates the create form', function () {
    fakeCustomers();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required'])
        ->assertSet('showForm', true);
});

it('creates a customer and closes the slide-over', function () {
    fakeCustomers();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', 'Carol Fahmy')
        ->set('form_email', 'carol@x.com')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/customers')
        && $req['name'] === 'Carol Fahmy');
});

it('falls back to sample data when the API is unavailable', function () {
    Http::fake(['*/api/provider/customers*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Index::class)
        ->assertSee('ليلى حسن') // fallback sample
        ->assertSee('sample data');
});
