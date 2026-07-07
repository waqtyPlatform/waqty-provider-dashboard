<?php

declare(strict_types=1);

use App\Livewire\Help\BugReport;
use App\Livewire\Help\Center;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders grouped FAQs', function () {
    Livewire::test(Center::class)
        ->assertSee('Getting started')
        ->assertSee('How do I add my first service?')
        ->assertSee('Bookings');
});

it('filters FAQs by search', function () {
    Livewire::test(Center::class)
        ->set('search', 'refund')
        ->assertSee('Can I issue a refund?')
        ->assertDontSee('How do I add my first service?');
});

it('shows an empty state when nothing matches', function () {
    Livewire::test(Center::class)
        ->set('search', 'zzzznotfound')
        ->assertSee(__('help.noResults'));
});

it('validates the bug report form', function () {
    Livewire::test(BugReport::class)
        ->set('description', 'too short')
        ->call('submit')
        ->assertHasErrors(['description' => 'min'])
        ->assertSet('submitted', false);
});

it('submits a bug report to the API', function () {
    Http::fake(['*/api/provider/bug-reports' => Http::response(['success' => true, 'data' => ['uuid' => 'B1']])]);

    Livewire::test(BugReport::class)
        ->set('category', 'bug')
        ->set('severity', 'high')
        ->set('description', 'The calendar does not load past bookings correctly.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/bug-reports')
        && $req['severity'] === 'high');
});

it('still confirms submission when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(BugReport::class)
        ->set('description', 'Something is broken on the dashboard page.')
        ->call('submit')
        ->assertSet('submitted', true);
});
