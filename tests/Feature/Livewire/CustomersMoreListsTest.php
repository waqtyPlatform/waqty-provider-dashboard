<?php

declare(strict_types=1);

use App\Livewire\Customers\LastVisits;
use App\Livewire\Customers\Reviews;
use App\Livewire\Customers\Statements;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// ── Statements ──────────────────────────────────────────────
it('lists client statements with money KPIs', function () {
    Http::fake(['*/api/provider/clients/statements*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'S1', 'name' => 'ليلى حسن', 'phone' => '01012345678', 'total_bookings' => 24, 'completed_bookings' => 22, 'total_charged' => 1850000, 'total_paid' => 1800000, 'outstanding' => 50000, 'last_booking_date' => '2026-06-28'],
    ]])]);

    Livewire::test(Statements::class)
        ->assertSee('ليلى حسن')
        ->assertSee('18,500 EGP')   // total charged
        ->assertSee('500 EGP');     // outstanding
});

it('falls back to sample statements when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Statements::class)
        ->assertSee('sample data')
        ->assertSee('مريم عادل');
});

// ── Last Visits ─────────────────────────────────────────────
it('lists clients ordered by most recent visit', function () {
    Http::fake(['*/api/provider/clients*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'L1', 'name' => 'عميل قديم', 'phone' => '01000000000', 'total_bookings' => 3, 'last_booking_date' => '2026-01-01'],
        ['uuid' => 'L2', 'name' => 'عميل حديث', 'phone' => '01111111111', 'total_bookings' => 9, 'last_booking_date' => '2026-07-02'],
    ]])]);

    Livewire::test(LastVisits::class)
        ->assertSeeInOrder(['عميل حديث', 'عميل قديم']); // sorted desc by last visit
});

it('falls back to sample last-visits when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(LastVisits::class)
        ->assertSee('sample data')
        ->assertSee('مريم عادل');
});

// ── Reviews (moderation) ────────────────────────────────────
function fakeReviews(): void
{
    Http::fake([
        '*/api/provider/reviews/*/flag' => Http::response(['success' => true], 200),
        '*/api/provider/ratings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'R1', 'rating' => 5, 'comment' => 'عمل رائع', 'status' => 'published', 'user' => ['name' => 'ليلى حسن'], 'created_at' => '2026-06-28 17:00:00'],
            ['uuid' => 'R2', 'rating' => 2, 'comment' => 'زيارة مخيّبة', 'status' => 'pending', 'user' => ['name' => 'عمر خالد'], 'created_at' => '2026-06-10 19:30:00'],
        ]]),
    ]);
}

it('lists reviews with average-rating KPI', function () {
    fakeReviews();

    Livewire::test(Reviews::class)
        ->assertSee('عمل رائع')
        ->assertSee('ليلى حسن')
        ->assertSee('3.5'); // avg of 5 and 2
});

it('filters reviews by rating', function () {
    fakeReviews();

    Livewire::test(Reviews::class)
        ->set('ratingFilter', '2')
        ->assertSee('زيارة مخيّبة')
        ->assertDontSee('عمل رائع');
});

it('reports a review via the flag endpoint', function () {
    fakeReviews();

    Livewire::test(Reviews::class)
        ->call('openReport', 'R1')
        ->assertSet('showReport', true)
        ->set('reportReason', 'Contains spam links')
        ->call('submitReport')
        ->assertSet('showReport', false)
        ->assertSet('overrides.R1', 'reported');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/reviews/R1/flag'));
});

it('validates the report reason', function () {
    fakeReviews();

    Livewire::test(Reviews::class)
        ->call('openReport', 'R1')
        ->set('reportReason', '')
        ->call('submitReport')
        ->assertHasErrors('reportReason')
        ->assertSet('showReport', true);
});

it('falls back to sample reviews when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Reviews::class)
        ->assertSee('sample data')
        ->assertSee('خدمة رائعة، سارة هي الأفضل! أنصح بها بشدة.');
});
