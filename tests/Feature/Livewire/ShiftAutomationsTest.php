<?php

declare(strict_types=1);

use App\Livewire\Settings\ShiftAutomations;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists shift automations', function () {
    Http::fake(['*/api/provider/settings/shift-automations' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'SA1', 'name' => 'Morning Notify', 'trigger' => 'shift_start', 'action' => 'notify_manager', 'active' => true],
        ['uuid' => 'SA2', 'name' => 'End Reminder', 'trigger' => 'shift_end', 'action' => 'send_reminder', 'active' => true],
    ]])]);

    Livewire::test(ShiftAutomations::class)
        ->assertSee('Morning Notify')
        ->assertSee('End Reminder');
});

it('creates a shift automation', function () {
    Http::fake(['*/api/provider/settings/shift-automations' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'SA1', 'name' => 'Morning Notify', 'trigger' => 'shift_start', 'action' => 'notify_manager', 'active' => true],
    ]])]);

    Livewire::test(ShiftAutomations::class)
        ->call('openCreate')
        ->set('form_name', 'Missed Shift Ping')
        ->set('form_trigger', 'missed_shift')
        ->set('form_action', 'notify_manager')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/shift-automations')
        && $req['name'] === 'Missed Shift Ping'
        && $req['trigger'] === 'missed_shift');
});

it('falls back to sample shift automations when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ShiftAutomations::class)
        ->assertSee('sample data')
        ->assertSee('تنبيه التأخير');
});
