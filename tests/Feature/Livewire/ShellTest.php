<?php

declare(strict_types=1);

use App\Livewire\App\CommandPalette;
use App\Livewire\Auth\EmployeeLogin;
use App\Livewire\Auth\ForgotPassword;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists navigation destinations in the command palette', function () {
    Livewire::test(CommandPalette::class)
        ->assertSee('Dashboard')       // group with its own href
        ->set('search', 'calendar')
        ->assertSee('Calendar')        // child under Bookings
        ->assertDontSee('Marketing');  // filtered out
});

it('walks the forgot-password wizard end to end', function () {
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['valid' => true]], 200)]);

    Livewire::test(ForgotPassword::class)
        ->set('identifier', 'clinic@waqty.com')
        ->call('sendCode')
        ->assertSet('step', 2)
        ->set('otp', '123456')
        ->call('verifyCode')
        ->assertSet('step', 3)
        ->set('newPassword', 'Password1')
        ->set('confirmPassword', 'Password1')
        ->call('resetPassword')
        ->assertSet('step', 4)
        ->assertHasNoErrors();
});

it('validates the reset identifier', function () {
    Livewire::test(ForgotPassword::class)
        ->set('identifier', 'not-valid')
        ->call('sendCode')
        ->assertHasErrors('identifier')
        ->assertSet('step', 1);
});

it('rejects a mismatched password confirmation', function () {
    Livewire::test(ForgotPassword::class)
        ->set('step', 3)
        ->set('newPassword', 'Password1')
        ->set('confirmPassword', 'different')
        ->call('resetPassword')
        ->assertHasErrors('confirmPassword')
        ->assertSet('step', 3);
});

it('validates employee login fields', function () {
    Livewire::test(EmployeeLogin::class)
        ->set('email', '')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['email', 'password']);
});

it('authenticates an employee and redirects to the portal', function () {
    Http::fake(['*/api/employee/auth/login' => Http::response(['success' => true, 'data' => [
        'token' => 'emp-token', 'employee' => ['name' => 'Khaled Hassan'],
    ]], 200)]);

    Livewire::test(EmployeeLogin::class)
        ->set('email', 'khaled@waqty.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('employee-portal.dashboard'));

    expect(session(config('waqty.session.employee_token')))->toBe('emp-token');
});
