<?php

declare(strict_types=1);

use App\Livewire\Auth\OnboardingWizard;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('validates the account step', function () {
    Livewire::test(OnboardingWizard::class)
        ->set('fullName', '')
        ->set('email', 'not-an-email')
        ->set('phone', '123')
        ->set('password', 'x')
        ->call('submitAccount')
        ->assertHasErrors(['fullName', 'email', 'phone', 'password', 'acceptedTerms'])
        ->assertSet('step', 1);
});

it('advances from account to OTP', function () {
    Http::fake(['*' => Http::response(['success' => true], 200)]);

    Livewire::test(OnboardingWizard::class)
        ->set('fullName', 'Sara Ahmed')
        ->set('email', 'sara@example.com')
        ->set('phone', '01012345678')
        ->set('password', 'secret123')
        ->set('acceptedTerms', true)
        ->call('submitAccount')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('advances through OTP and business into services with presets', function () {
    Http::fake(['*' => Http::response(['success' => true, 'data' => ['valid' => true]], 200)]);

    Livewire::test(OnboardingWizard::class)
        ->set('step', 2)
        ->set('email', 'sara@example.com')
        ->set('otp', '123456')
        ->call('verifyOtp')
        ->assertSet('step', 3)
        ->set('businessType', 'barber')
        ->set('businessName', 'Kings Barbershop')
        ->set('governorate', 'Cairo')
        ->set('city', 'Maadi')
        ->set('branchEmail', 'branch@example.com')
        ->set('branchPassword', 'secret123')
        ->call('submitBusiness')
        ->assertSet('step', 4)
        ->assertSet('selectedServices', ['قصّة شعر', 'تهذيب اللحية', 'حلاقة', 'غسيل شعر', 'قصّة شعر للأطفال']);
});

it('adds and toggles services then finishes into the dashboard', function () {
    $component = Livewire::test(OnboardingWizard::class)
        ->set('step', 4)
        ->set('businessType', 'salon')
        ->set('fullName', 'Sara Ahmed')
        ->set('email', 'sara@example.com')
        ->set('businessName', 'Elite Salon')
        ->call('toggleService', 'Manicure')
        ->set('customService', 'Bridal Package')
        ->call('addCustomService');

    expect($component->get('selectedServices'))->toContain('Bridal Package');

    $component->call('finish')
        ->assertRedirect(route('dashboard'));

    expect(session(config('waqty.session.provider_token')))->toBe('onboarding-token')
        ->and(session('waqty.new_workspace'))->toBeTrue();
});

it('cascades cities from the selected governorate', function () {
    $component = Livewire::test(OnboardingWizard::class)
        ->set('governorate', 'Giza');

    expect($component->instance()->cities())->toContain('Dokki', 'Mohandessin');
});
