<?php

declare(strict_types=1);

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\BusinessHours;
use App\Livewire\Settings\General;
use App\Livewire\Settings\Invoice;
use App\Livewire\Settings\Localization;
use App\Livewire\Settings\Loyalty;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\PaymentMethods;
use App\Livewire\Settings\PettyCashItems;
use App\Livewire\Settings\Resources;
use App\Livewire\Settings\Safes;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\ServiceCategories;
use App\Livewire\Settings\Tipping;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// ── General ─────────────────────────────────────────────────
it('saves general settings to the session', function () {
    Livewire::test(General::class)
        ->set('onlineBooking', false)
        ->set('defaultGap', 30)
        ->call('save')
        ->assertHasNoErrors();

    expect(session('waqty.settings.general.defaultGap'))->toBe(30)
        ->and(session('waqty.settings.general.onlineBooking'))->toBeFalse();
});

it('validates general scheduling numbers', function () {
    Livewire::test(General::class)
        ->set('defaultGap', 999)
        ->call('save')
        ->assertHasErrors('defaultGap');
});

// ── Business Hours ──────────────────────────────────────────
it('loads business hours from the API and saves them', function () {
    Http::fake([
        '*/api/provider/settings/business-hours' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'H0', 'day' => 0, 'open_time' => '10:00', 'close_time' => '22:00', 'is_closed' => false],
            ['uuid' => 'H1', 'day' => 1, 'open_time' => '09:00', 'close_time' => '20:00', 'is_closed' => false],
        ]]),
    ]);

    Livewire::test(BusinessHours::class)
        ->assertSee('Sunday')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT' && str_contains($req->url(), '/settings/business-hours'));
});

it('falls back to default hours when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(BusinessHours::class)
        ->assertSet('fallbackUsed', true)
        ->assertSee('Sunday')
        ->assertSee('Friday');
});

// ── Payment Methods ─────────────────────────────────────────
function fakePaymentMethods(): void
{
    Http::fake([
        '*/api/provider/settings/payment-methods' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'PM1', 'name' => 'Cash', 'type' => 'cash', 'fee_percentage' => 0, 'active' => true],
            ['uuid' => 'PM2', 'name' => 'Visa', 'type' => 'card', 'fee_percentage' => 2.5, 'active' => true],
        ]]),
    ]);
}

it('lists payment methods', function () {
    fakePaymentMethods();

    Livewire::test(PaymentMethods::class)
        ->assertSee('Visa')
        ->assertSee('2.5%');
});

it('creates a payment method', function () {
    fakePaymentMethods();

    Livewire::test(PaymentMethods::class)
        ->call('openCreate')
        ->set('form_name', 'InstaPay')
        ->set('form_type', 'wallet')
        ->set('form_fee', '1.5')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/payment-methods')
        && $req['name'] === 'InstaPay');
});

it('falls back to sample payment methods when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PaymentMethods::class)
        ->assertSee('sample data')
        ->assertSee('فودافون كاش');
});

// ── Localization ────────────────────────────────────────────
it('saves localization preferences to the session', function () {
    Livewire::test(Localization::class)
        ->set('timezone', 'Asia/Riyadh')
        ->set('currency', 'SAR')
        ->call('save')
        ->assertHasNoErrors();

    expect(session('waqty.settings.localization.currency'))->toBe('SAR')
        ->and(session('waqty.settings.localization.timezone'))->toBe('Asia/Riyadh');
});

it('rejects an unknown currency', function () {
    Livewire::test(Localization::class)
        ->set('currency', 'GBP')
        ->call('save')
        ->assertHasErrors('currency');
});

// ── Appearance ──────────────────────────────────────────────
it('saves appearance preferences to the session', function () {
    Livewire::test(Appearance::class)
        ->set('brandColor', '#3b82f6')
        ->set('compactSidebar', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(session('waqty.settings.appearance.brandColor'))->toBe('#3b82f6')
        ->and(session('waqty.settings.appearance.compactSidebar'))->toBeTrue();
});

it('rejects an invalid brand colour', function () {
    Livewire::test(Appearance::class)
        ->set('brandColor', 'blue')
        ->call('save')
        ->assertHasErrors('brandColor');
});

// ── Notifications ───────────────────────────────────────────
it('loads notification settings from the API and saves them', function () {
    Http::fake([
        '*/api/provider/settings/notifications' => Http::response(['success' => true, 'data' => [
            ['type' => 'newBooking', 'push' => true, 'email' => false],
            ['type' => 'dailySummary', 'push' => false, 'email' => true],
        ]]),
    ]);

    Livewire::test(Notifications::class)
        ->assertSet('prefs.newBooking.push', true)
        ->assertSet('prefs.dailySummary.email', true)
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/settings/notifications')
        && is_array($req['settings']));
});

it('falls back to default notification settings when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Notifications::class)
        ->assertSet('fallbackUsed', true)
        ->assertSee('New booking alerts');
});

// ── Invoice ─────────────────────────────────────────────────
it('loads invoice settings from the API and saves them', function () {
    Http::fake(['*/api/provider/settings/invoice' => Http::response(['success' => true, 'data' => [
        'business_name' => 'Glow Clinic', 'prefix' => 'GC-', 'next_number' => 500,
        'tax_rate' => 14, 'currency' => 'EGP',
    ]])]);

    Livewire::test(Invoice::class)
        ->assertSet('businessName', 'Glow Clinic')
        ->assertSet('prefix', 'GC-')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/settings/invoice')
        && $req['business_name'] === 'Glow Clinic');
});

it('validates the invoice currency', function () {
    Http::fake(['*/api/provider/settings/invoice' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Invoice::class)
        ->set('businessName', 'X')
        ->set('currency', 'GBP')
        ->call('save')
        ->assertHasErrors('currency');
});

// ── Tipping ─────────────────────────────────────────────────
it('adds, sorts and de-duplicates tip percentages', function () {
    Http::fake(['*/api/provider/settings/tipping' => Http::response(['success' => true, 'data' => [
        'enabled' => true, 'percentages' => [10, 15, 20], 'allow_custom' => true, 'distribution' => 'individual',
    ]])]);

    $c = Livewire::test(Tipping::class)
        ->set('newPercentage', '5')
        ->call('addPercentage')
        ->assertHasNoErrors();

    expect($c->get('percentages'))->toBe([5, 10, 15, 20]);

    $c->set('newPercentage', '10')->call('addPercentage');
    expect($c->get('percentages'))->toBe([5, 10, 15, 20]);
});

it('rejects an out-of-range tip percentage', function () {
    Http::fake(['*/api/provider/settings/tipping' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test(Tipping::class)
        ->set('newPercentage', '250')
        ->call('addPercentage')
        ->assertHasErrors('newPercentage');
});

it('saves tipping settings to the API', function () {
    Http::fake(['*/api/provider/settings/tipping' => Http::response(['success' => true, 'data' => [
        'enabled' => true, 'percentages' => [10, 15, 20], 'allow_custom' => true, 'distribution' => 'individual',
    ]])]);

    Livewire::test(Tipping::class)
        ->set('distribution', 'pool')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/settings/tipping')
        && $req['distribution'] === 'pool');
});

// ── Loyalty ─────────────────────────────────────────────────
it('falls back to a default tier ladder when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    $tiers = Livewire::test(Loyalty::class)
        ->assertSet('fallbackUsed', true)
        ->get('tiers');

    expect($tiers)->toHaveCount(4)
        ->and($tiers[0]['name'])->toBe('Bronze')
        ->and($tiers[3]['name'])->toBe('Platinum');
});

it('adds and removes loyalty tiers', function () {
    Http::fake(['*/api/provider/settings/loyalty' => Http::response(['success' => true, 'data' => [
        'enabled' => true, 'tiers' => [
            ['name' => 'Basic', 'min_points' => 0, 'discount' => 0, 'color' => '#111111'],
        ],
    ]])]);

    $c = Livewire::test(Loyalty::class);
    expect($c->get('tiers'))->toHaveCount(1);

    $c->call('addTier');
    expect($c->get('tiers'))->toHaveCount(2);

    $c->call('removeTier', 0);
    expect($c->get('tiers'))->toHaveCount(1);
});

it('saves loyalty settings to the API', function () {
    Http::fake(['*/api/provider/settings/loyalty' => Http::response(['success' => true, 'data' => [
        'enabled' => true, 'tiers' => [
            ['name' => 'Basic', 'min_points' => 0, 'discount' => 0, 'color' => '#111111'],
        ],
    ]])]);

    Livewire::test(Loyalty::class)
        ->set('pointsPerEgp', '2')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/settings/loyalty')
        && (float) $req['points_per_egp'] === 2.0);
});

// ── Safes ───────────────────────────────────────────────────
function fakeSafes(): void
{
    Http::fake(['*/api/provider/settings/safes' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'SF1', 'name' => 'Front Desk Safe', 'branch' => 'Downtown', 'balance' => 1250000, 'active' => true, 'last_activity' => '2h ago'],
        ['uuid' => 'SF2', 'name' => 'Main Vault', 'branch' => 'Downtown', 'balance' => 8400000, 'active' => true, 'last_activity' => '1d ago'],
    ]])]);
}

it('lists safes with formatted balances', function () {
    fakeSafes();

    Livewire::test(Safes::class)
        ->assertSee('Main Vault')
        ->assertSee('12,500');
});

it('creates a safe with a minor-unit balance', function () {
    fakeSafes();

    Livewire::test(Safes::class)
        ->call('openCreate')
        ->set('form_name', 'New Vault')
        ->set('form_balance', '500')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/safes')
        && $req['name'] === 'New Vault'
        && $req['balance'] === 50000);
});

it('falls back to sample safes when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Safes::class)
        ->assertSee('sample data')
        ->assertSee('الخزنة الرئيسية');
});

// ── Resources ───────────────────────────────────────────────
function fakeResources(): void
{
    Http::fake(['*/api/provider/settings/resources' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'RS1', 'name' => 'Styling Station 1', 'type' => 'chair', 'capacity' => 1, 'status' => 'active'],
        ['uuid' => 'RS3', 'name' => 'Treatment Room A', 'type' => 'room', 'capacity' => 2, 'status' => 'active'],
    ]])]);
}

it('lists resources', function () {
    fakeResources();

    Livewire::test(Resources::class)
        ->assertSee('Styling Station 1')
        ->assertSee('Treatment Room A');
});

it('creates a resource', function () {
    fakeResources();

    Livewire::test(Resources::class)
        ->call('openCreate')
        ->set('form_name', 'Pedicure Chair')
        ->set('form_type', 'chair')
        ->set('form_capacity', 1)
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/resources')
        && $req['name'] === 'Pedicure Chair'
        && $req['type'] === 'chair');
});

it('validates resource capacity', function () {
    fakeResources();

    Livewire::test(Resources::class)
        ->call('openCreate')
        ->set('form_name', 'Bad Chair')
        ->set('form_capacity', 0)
        ->call('save')
        ->assertHasErrors('form_capacity');
});

it('falls back to sample resources when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Resources::class)
        ->assertSee('sample data')
        ->assertSee('جهاز الليزر');
});

// ── Petty Cash Items ────────────────────────────────────────
function fakePetty(): void
{
    Http::fake(['*/api/provider/settings/petty-cash-items' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'PC1', 'name' => 'Office Supplies', 'category' => 'administrative', 'default_amount' => 50000, 'active' => true],
        ['uuid' => 'PC2', 'name' => 'Coffee', 'category' => 'kitchen', 'default_amount' => 30000, 'active' => true],
    ]])]);
}

it('lists petty cash items', function () {
    fakePetty();

    Livewire::test(PettyCashItems::class)
        ->assertSee('Office Supplies')
        ->assertSee('Administrative');
});

it('creates a petty cash item with a minor-unit limit', function () {
    fakePetty();

    Livewire::test(PettyCashItems::class)
        ->call('openCreate')
        ->set('form_name', 'Parking')
        ->set('form_category', 'transportation')
        ->set('form_limit', '75')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/petty-cash-items')
        && $req['name'] === 'Parking'
        && $req['default_amount'] === 7500);
});

it('falls back to sample petty cash items when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PettyCashItems::class)
        ->assertSee('sample data')
        ->assertSee('إصلاح المعدات');
});

// ── Security ────────────────────────────────────────────────
it('saves security preferences to the session', function () {
    Livewire::test(Security::class)
        ->set('twoFactor', true)
        ->set('sessionTimeout', 60)
        ->call('save')
        ->assertHasNoErrors();

    expect(session('waqty.settings.security.twoFactor'))->toBeTrue()
        ->and(session('waqty.settings.security.sessionTimeout'))->toBe(60);
});

it('validates the session timeout', function () {
    Livewire::test(Security::class)
        ->set('sessionTimeout', 1)
        ->call('save')
        ->assertHasErrors('sessionTimeout');
});

// ── Service Categories ──────────────────────────────────────
function fakeCategories(): void
{
    Http::fake(['*/api/provider/settings/service-categories' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'CAT1', 'name' => 'Hair Styling', 'color' => '#8b5cf6', 'services_count' => 8, 'active' => true],
        ['uuid' => 'CAT2', 'name' => 'Nails', 'color' => '#ec4899', 'services_count' => 5, 'active' => true],
    ]])]);
}

it('lists service categories', function () {
    fakeCategories();

    Livewire::test(ServiceCategories::class)
        ->assertSee('Hair Styling')
        ->assertSee('Nails');
});

it('creates a service category', function () {
    fakeCategories();

    Livewire::test(ServiceCategories::class)
        ->call('openCreate')
        ->set('form_name', 'Waxing')
        ->set('form_color', '#10b981')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/service-categories')
        && $req['name'] === 'Waxing'
        && $req['color'] === '#10b981');
});

it('falls back to sample service categories when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ServiceCategories::class)
        ->assertSee('sample data')
        ->assertSee('العناية بالبشرة');
});
