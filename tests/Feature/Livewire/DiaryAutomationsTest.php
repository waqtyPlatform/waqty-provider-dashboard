<?php

declare(strict_types=1);

use App\Livewire\Settings\DiaryAutomations;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists diary automations', function () {
    Http::fake(['*/api/provider/settings/diary-automations' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'DA1', 'name' => 'Booking Reminder', 'trigger' => 'booking_created', 'action' => 'send_sms', 'active' => true],
        ['uuid' => 'DA2', 'name' => 'Win-back Message', 'trigger' => 'no_show', 'action' => 'send_email', 'active' => true],
    ]])]);

    Livewire::test(DiaryAutomations::class)
        ->assertSee('Booking Reminder')
        ->assertSee('Win-back Message');
});

it('creates a diary automation', function () {
    Http::fake(['*/api/provider/settings/diary-automations' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'DA1', 'name' => 'Booking Reminder', 'trigger' => 'booking_created', 'action' => 'send_sms', 'active' => true],
    ]])]);

    Livewire::test(DiaryAutomations::class)
        ->call('openCreate')
        ->set('form_name', 'Follow-up Nudge')
        ->set('form_trigger', 'booking_cancelled')
        ->set('form_action', 'notify_staff')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/diary-automations')
        && $req['name'] === 'Follow-up Nudge'
        && $req['trigger'] === 'booking_cancelled');
});

it('falls back to sample diary automations when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(DiaryAutomations::class)
        ->assertSee('sample data')
        ->assertSee('تهنئة عيد ميلاد');
});
