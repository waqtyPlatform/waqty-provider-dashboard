<?php

declare(strict_types=1);

use App\Livewire\Marketing\Hub;
use Livewire\Livewire;

it('renders the marketing hub with the offers preview and sample banner', function () {
    Livewire::test(Hub::class)
        ->assertOk()
        ->assertSee('انطلاقة الصيف')   // Arabic sample offer
        ->assertSee('ترحيب العملاء الجدد')
        ->assertSee('sample data');     // demo-mode banner (common.sampleData)
});

it('shows the promo, message and campaign previews when switching tabs', function () {
    Livewire::test(Hub::class)
        ->set('tab', 'promos')
        ->assertSee('SUMMER20')
        ->set('tab', 'messages')
        ->assertSee('طلب تقييم')
        ->set('tab', 'campaigns')
        ->assertSee('حملة تخفيضات الصيف');
});

it('exposes hub KPIs and the per-tab view-all route', function () {
    $hub = Livewire::test(Hub::class)->instance();

    expect($hub->kpis()['activeOffers'])->toBe(3)
        ->and($hub->kpis()['redemptions'])->toBe(264)
        ->and($hub->viewAllRoute())->toBe('marketing.offers');
});
