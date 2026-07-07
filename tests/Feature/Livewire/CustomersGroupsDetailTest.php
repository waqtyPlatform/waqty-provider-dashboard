<?php

declare(strict_types=1);

use App\Livewire\Customers\Detail;
use App\Livewire\Customers\Groups;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists customer groups from the API', function () {
    Http::fake(['*/api/provider/customer-groups*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'G1', 'name' => 'VIP', 'discount_percentage' => 15, 'color' => '#f59e0b', 'customers_count' => 24],
    ]])]);

    Livewire::test(Groups::class)
        ->assertSee('VIP')
        ->assertSee('15% Discount');
});

it('validates and creates a customer group', function () {
    Http::fake(['*/api/provider/customer-groups*' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Groups::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required']);

    Livewire::test(Groups::class)
        ->call('openCreate')
        ->set('form_name', 'Students')
        ->set('form_discount', '10')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/customer-groups')
        && $req['name'] === 'Students'
        && (float) $req['discount_percentage'] === 10.0);
});

it('falls back to sample groups when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Groups::class)
        ->assertSee('sample data')
        ->assertSee('VIP');
});

function fakeCustomerDetail(): void
{
    Http::fake([
        '*/api/provider/customers/C1/statements*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'ST1', 'type' => 'debit', 'amount' => 45000, 'balance' => 45000, 'description' => 'صبغة شعر', 'created_at' => '2026-06-28 15:20:00'],
        ]]),
        '*/api/provider/customers/C1/reviews' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'RV1', 'rating' => 5, 'comment' => 'ممتاز!', 'service' => ['name' => 'صبغة شعر'], 'status' => 'published', 'direction' => 'by_customer'],
        ]]),
        '*/api/provider/customers/C1/staff-notes' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'N1', 'note' => 'تفضّل سارة', 'employee' => ['name' => 'منى']],
        ]]),
        '*/api/provider/customers/C1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'C1', 'name' => 'ليلى حسن', 'phone' => '01012345678', 'vip' => true,
            'group' => ['name' => 'VIP'], 'total_visits' => 24, 'total_spent' => 1850000, 'allergies' => 'البنسلين',
        ]]),
    ]);
}

it('shows the customer profile with medical alert', function () {
    fakeCustomerDetail();

    Livewire::test(Detail::class, ['uuid' => 'C1'])
        ->assertSee('ليلى حسن')
        ->assertSee('18,500 EGP')      // total spent (1,850,000 minor units)
        ->assertSee('البنسلين');     // medical alert
});

it('switches to the statements tab and renders the ledger', function () {
    fakeCustomerDetail();

    Livewire::test(Detail::class, ['uuid' => 'C1'])
        ->set('tab', 'statements')
        ->assertSee('صبغة شعر')
        ->assertSee('450 EGP');
});

it('updates medical info via the API', function () {
    fakeCustomerDetail();
    Http::fake(['*/api/provider/customers/C1' => Http::response(['success' => true, 'data' => ['uuid' => 'C1', 'name' => 'ليلى']], 200)]);

    Livewire::test(Detail::class, ['uuid' => 'C1'])
        ->call('openEdit')
        ->set('form_allergies', 'Latex')
        ->call('saveMedical')
        ->assertSet('showEdit', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/api/provider/customers/C1')
        && $req['allergies'] === 'Latex');
});

it('optimistically adds a staff note in fallback mode', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Detail::class, ['uuid' => 'C1'])
        ->set('tab', 'notes')
        ->set('noteText', 'تم الاتصال لتأكيد الموعد')
        ->call('addNote')
        ->assertSet('noteText', '')
        ->assertSee('تم الاتصال لتأكيد الموعد');
});
