<?php

declare(strict_types=1);

use App\Livewire\Employees\Detail;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeEmployee(): void
{
    Http::fake([
        '*/api/provider/employees/E1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'E1',
            'name' => 'Sara Ahmed',
            'email' => 'sara@waqty.com',
            'phone' => '01012345678',
            'branch' => ['uuid' => 'B1', 'name' => 'Downtown'],
            'active' => true,
            'blocked' => false,
            'role' => 'manager',
            'position' => 'Senior Stylist',
            'rating' => 4.7,
            'bookings' => 142,
            'revenue' => 5000000, // 50,000 EGP in minor units
            'clients' => 96,
        ]]),
    ]);
}

it('renders the employee header and overview KPIs from the API', function () {
    fakeEmployee();

    Livewire::test(Detail::class, ['uuid' => 'E1'])
        ->assertSee('Sara Ahmed')
        ->assertSee('Senior Stylist')
        ->assertSee('50,000')   // formatted revenue KPI
        ->assertSee('142')      // bookings KPI
        ->assertSee('4.7');     // rating KPI
});

it('falls back to Arabic sample data when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Detail::class, ['uuid' => 'E1'])
        ->assertSee('sample data')
        ->assertSee('د. سارة أحمد')
        ->assertSee('أخصائية عناية بالبشرة');
});

it('reveals the mock services and activity panels when switching tabs', function () {
    fakeEmployee();

    Livewire::test(Detail::class, ['uuid' => 'E1'])
        ->set('tab', 'services')
        ->assertSee('صبغة شعر')
        ->set('tab', 'activity')
        ->assertSee('حصلت على تقييم 5 نجوم من نور علي');
});

it('validates the name and notifies from the edit stub', function () {
    fakeEmployee();

    Livewire::test(Detail::class, ['uuid' => 'E1'])
        ->call('openEdit')
        ->assertSet('showEdit', true)
        ->set('form_name', '')
        ->call('saveEdit')
        ->assertHasErrors('form_name')
        ->set('form_name', 'Sara A.')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('showEdit', false)
        ->assertDispatched('notify');
});
