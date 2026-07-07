<?php

declare(strict_types=1);

use App\Livewire\Transactions\PettyCash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePettyCash(): void
{
    Http::fake([
        '*/api/provider/transactions/petty-cash/*/approve' => Http::response(['success' => true], 200),
        '*/api/provider/transactions/petty-cash/*/reject' => Http::response(['success' => true], 200),
        '*/api/provider/transactions/petty-cash' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/transactions/petty-cash*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'PC1', 'reference' => 'PC-100501', 'category' => 'ضيافة', 'description' => 'قهوة وضيافة للعملاء', 'amount' => 12000, 'requested_by' => 'سارة أحمد', 'status' => 'pending', 'date' => '2026-07-04'],
            ['uuid' => 'PC2', 'reference' => 'PC-100502', 'category' => 'مواصلات', 'description' => 'أجرة توصيل مستلزمات', 'amount' => 8000, 'requested_by' => 'منى عادل', 'status' => 'approved', 'date' => '2026-07-03'],
        ]]),
    ]);
}

it('falls back to sample petty-cash rows when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PettyCash::class)
        ->assertSee('sample data')
        ->assertSee('PC-100501')
        ->assertSee('قهوة وضيافة للعملاء');
});

it('validates and records a new petty-cash entry', function () {
    fakePettyCash();

    Livewire::test(PettyCash::class)
        ->call('openCreate')
        ->set('form_category', '')
        ->set('form_amount', '')
        ->set('form_description', '')
        ->call('save')
        ->assertHasErrors(['form_category', 'form_amount', 'form_description']);

    Livewire::test(PettyCash::class)
        ->call('openCreate')
        ->set('form_category', 'ضيافة')
        ->set('form_amount', '250')
        ->set('form_description', 'قهوة للاجتماع')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/transactions/petty-cash')
        && $req['amount'] === 25000
        && $req['category'] === 'ضيافة');
});

it('approves and rejects a pending petty-cash entry', function () {
    fakePettyCash();

    Livewire::test(PettyCash::class)
        ->call('approve', 'PC1')
        ->assertSet('overrides.PC1', 'approved')
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/transactions/petty-cash/PC1/approve'));

    Livewire::test(PettyCash::class)
        ->call('openReject', 'PC1')
        ->set('rejectReason', '')
        ->call('submitReject')
        ->assertHasErrors(['rejectReason'])
        ->set('rejectReason', 'خارج بنود الصرف')
        ->call('submitReject')
        ->assertSet('showReject', false)
        ->assertSet('overrides.PC1', 'rejected');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/transactions/petty-cash/PC1/reject')
        && $req['reason'] === 'خارج بنود الصرف');
});
