<?php

declare(strict_types=1);

use App\Livewire\Bookings\Rooms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders sample bookings when the API is unreachable', function () {
    Http::fake([
        '*/api/provider/bookings*' => Http::response('', 500),
    ]);

    Livewire::test(Rooms::class)
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد')
        ->assertSee('صبغة شعر');
});

it('changes the date with nextDay and prevDay', function () {
    Http::fake([
        '*/api/provider/bookings*' => Http::response('', 500),
    ]);

    $today = Carbon::today()->toDateString();
    $tomorrow = Carbon::today()->addDay()->toDateString();

    Livewire::test(Rooms::class)
        ->assertSet('date', $today)
        ->call('nextDay')
        ->assertSet('date', $tomorrow)
        ->call('prevDay')
        ->assertSet('date', $today);
});
