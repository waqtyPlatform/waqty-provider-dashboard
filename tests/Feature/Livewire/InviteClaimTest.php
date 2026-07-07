<?php

declare(strict_types=1);

use App\Livewire\Auth\InviteClaim;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders the phone step with the mounted token', function () {
    Livewire::test(InviteClaim::class, ['token' => 'invite-token-123'])
        ->assertSet('token', 'invite-token-123')
        ->assertSet('step', 1)
        ->assertSee(__('invite.invitedPhone'));
});

it('rejects a non-Egyptian phone number', function () {
    Livewire::test(InviteClaim::class, ['token' => 'abc'])
        ->set('phone', '123')
        ->call('sendCode')
        ->assertHasErrors(['phone'])
        ->assertSet('step', 1);
});

it('advances from phone to the code step', function () {
    Http::fake(['*' => Http::response(['success' => true], 200)]);

    Livewire::test(InviteClaim::class, ['token' => 'abc'])
        ->set('phone', '01012345678')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('requires the code and name before joining', function () {
    Livewire::test(InviteClaim::class, ['token' => 'abc'])
        ->set('step', 2)
        ->set('phone', '01012345678')
        ->set('otp', '')
        ->set('fullName', '')
        ->call('verify')
        ->assertHasErrors(['otp', 'fullName'])
        ->assertSet('step', 2);
});

it('verifies and shows the done panel linking to the employee portal', function () {
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['valid' => true]], 200)]);

    Livewire::test(InviteClaim::class, ['token' => 'abc'])
        ->set('step', 2)
        ->set('phone', '01012345678')
        ->set('otp', '123456')
        ->set('fullName', 'Sara Ahmed')
        ->call('verify')
        ->assertHasNoErrors()
        ->assertSet('step', 3)
        ->assertSee('/employee-portal/login');
});
