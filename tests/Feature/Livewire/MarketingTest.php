<?php

declare(strict_types=1);

use App\Livewire\Marketing\Offers;
use App\Livewire\Marketing\PromoCodes;
use Livewire\Livewire;

// ── Offers ──────────────────────────────────────────────────
it('lists seeded offers with KPIs', function () {
    Livewire::test(Offers::class)
        ->assertSee('انطلاقة الصيف')
        ->assertSee('ترحيب العملاء الجدد');
});

it('creates a percentage offer', function () {
    $component = Livewire::test(Offers::class)
        ->call('openCreate')
        ->set('form_name', 'Autumn Deal')
        ->set('form_type', 'percentage')
        ->set('form_value', '25')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->instance()->offers)->pluck('name'))->toContain('Autumn Deal');
});

it('validates the offer form', function () {
    Livewire::test(Offers::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_value', '')
        ->call('save')
        ->assertHasErrors(['form_name', 'form_value']);
});

// ── Promo Codes ─────────────────────────────────────────────
it('lists seeded promo codes', function () {
    Livewire::test(PromoCodes::class)
        ->assertSee('SUMMER20')
        ->assertSee('WELCOME50');
});

it('rejects an invalid promo code format', function () {
    Livewire::test(PromoCodes::class)
        ->call('openCreate')
        ->set('form_code', 'ab')            // too short + lowercase
        ->set('form_value', '10')
        ->call('save')
        ->assertHasErrors('form_code');
});

it('creates and uppercases a promo code', function () {
    $component = Livewire::test(PromoCodes::class)
        ->call('openCreate')
        ->set('form_code', 'newyear30')
        ->set('form_type', 'percentage')
        ->set('form_value', '30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(collect($component->instance()->codes)->pluck('code'))->toContain('NEWYEAR30');
});

it('caps a percentage promo at 100', function () {
    Livewire::test(PromoCodes::class)
        ->call('openCreate')
        ->set('form_code', 'HUGE')
        ->set('form_type', 'percentage')
        ->set('form_value', '150')
        ->call('save')
        ->assertHasErrors('form_value');
});
