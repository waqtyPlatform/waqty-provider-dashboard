<?php

declare(strict_types=1);

use App\Livewire\Employees\Fingerprints;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeFingerprints(): void
{
    Http::fake([
        '*/api/provider/fingerprints/enroll' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/fingerprints/*' => Http::response(['success' => true], 200),
        '*/api/provider/fingerprints*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'FP1', 'employee' => 'Sara Ahmed', 'department' => 'Hair', 'status' => 'enrolled', 'fingers' => 2, 'last_sync' => '2h ago'],
            ['uuid' => 'FP2', 'employee' => 'Khaled Hassan', 'department' => 'Reception', 'status' => 'not_enrolled', 'fingers' => 0, 'last_sync' => null],
        ]]),
    ]);
}

it('lists fingerprint records from the API', function () {
    fakeFingerprints();

    Livewire::test(Fingerprints::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Reception');
});

it('falls back to Arabic sample records when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Fingerprints::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('قسم الأظافر');
});

it('filters records by enrollment status', function () {
    fakeFingerprints();

    Livewire::test(Fingerprints::class)
        ->set('statusFilter', 'not_enrolled')
        ->assertSee('Khaled Hassan')
        ->assertDontSee('Sara Ahmed');
});

it('validates the fingers count before enrolling', function () {
    fakeFingerprints();

    Livewire::test(Fingerprints::class)
        ->call('openEnroll', 'FP2')
        ->set('fingersCount', '')
        ->call('enrollFingerprint')
        ->assertHasErrors(['fingersCount'])
        ->assertSet('showEnroll', true);
});

it('enrolls a fingerprint and notifies', function () {
    fakeFingerprints();

    Livewire::test(Fingerprints::class)
        ->call('openEnroll', 'FP2')
        ->assertSet('isReenroll', false)
        ->set('fingersCount', '2')
        ->call('enrollFingerprint')
        ->assertSet('showEnroll', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/fingerprints/enroll')
        && $req['employee_uuid'] === 'FP2'
        && $req['fingers'] === 2
        && $req['status'] === 'enrolled');
});

it('clears an enrolled fingerprint and notifies', function () {
    fakeFingerprints();

    Livewire::test(Fingerprints::class)
        ->call('confirmClear', 'FP1')
        ->assertSet('showClear', true)
        ->call('clearFingerprint')
        ->assertSet('showClear', false)
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'DELETE'
        && str_contains($req->url(), '/api/provider/fingerprints/FP1'));
});
