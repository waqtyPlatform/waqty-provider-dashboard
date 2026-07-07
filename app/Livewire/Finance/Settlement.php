<?php

declare(strict_types=1);

namespace App\Livewire\Finance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settlement & payouts — what the platform owes the provider after commission
 * and transaction fees. [MOCK] in the source (no dedicated endpoint), so this
 * renders a faithful read-only view from sample data.
 */
#[Layout('components.layouts.app')]
#[Title('Settlement — Waqty')]
class Settlement extends Component
{
    /** @return array{gross:int, commission:int, fees:int, net:int} */
    public function summary(): array
    {
        $gross = 5480000;
        $commission = (int) round($gross * 0.10);
        $fees = (int) round($gross * 0.025);

        return ['gross' => $gross, 'commission' => $commission, 'fees' => $fees, 'net' => $gross - $commission - $fees];
    }

    /** @return array<int, array<string, mixed>> */
    public function payouts(): array
    {
        return [
            ['period' => 'Jun 2026', 'gross' => 5480000, 'net' => 4652000, 'status' => 'paid'],
            ['period' => 'May 2026', 'gross' => 4920000, 'net' => 4176800, 'status' => 'paid'],
            ['period' => 'Jul 2026', 'gross' => 1240000, 'net' => 1053200, 'status' => 'processing'],
            ['period' => 'Apr 2026', 'gross' => 5110000, 'net' => 4339200, 'status' => 'paid'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function ledger(): array
    {
        return [
            ['visit' => 'BK-1042', 'date' => '2026-07-03', 'rate' => 10, 'commission' => 4500],
            ['visit' => 'BK-1039', 'date' => '2026-07-03', 'rate' => 10, 'commission' => 1500],
            ['visit' => 'BK-1036', 'date' => '2026-07-02', 'rate' => 12, 'commission' => 6600],
            ['visit' => 'BK-1030', 'date' => '2026-07-02', 'rate' => 10, 'commission' => 5500],
            ['visit' => 'BK-1021', 'date' => '2026-07-01', 'rate' => 10, 'commission' => 15000],
        ];
    }

    public function render()
    {
        return view('livewire.finance.settlement');
    }
}
