<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shifts — Waqty')]
class Shifts extends Component
{
    use HandlesWaqtyErrors;

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    // Close confirmation dialog
    public bool $showClose = false;

    public ?string $closingUuid = null;

    /** Optimistically closed shifts (mirrors the source FALLBACK optimistic pattern). @var array<string, bool> */
    public array $closedOverrides = [];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(FinanceService::class)->shiftTotals(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = $this->fallbackData();
        }

        // Apply optimistic close overrides (drawer assumed to match expected).
        $this->loaded = array_map(function (array $row) {
            $uuid = $row['uuid'] ?? null;
            if ($uuid !== null && ! empty($this->closedOverrides[$uuid])) {
                $row['status'] = 'closed';
                $row['closed_at'] = now()->toDateTimeString();
                $row['actual_total'] = (int) ($row['expected_total'] ?? 0);
                $row['variance'] = 0;
            }

            return $row;
        }, $this->loaded);

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $status = $this->statusFilter;

        return array_values(array_filter(
            $this->source(),
            fn (array $r) => $status === 'all' || ($r['status'] ?? '') === $status
        ));
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->filtered(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered());
    }

    /** @return array{open:int, variance:int, today:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $today = Carbon::today()->toDateString();

        $open = count(array_filter($all, fn (array $r) => ($r['status'] ?? '') === 'open'));
        $variance = array_sum(array_map(
            fn (array $r) => ($r['status'] ?? '') === 'closed' ? (int) ($r['variance'] ?? 0) : 0,
            $all
        ));
        $todayCount = count(array_filter(
            $all,
            fn (array $r) => isset($r['opened_at']) && Carbon::parse($r['opened_at'])->toDateString() === $today
        ));

        return ['open' => $open, 'variance' => (int) $variance, 'today' => $todayCount];
    }

    public function openClose(string $uuid): void
    {
        $this->closingUuid = $uuid;
        $this->showClose = true;
    }

    public function close(): void
    {
        $uuid = $this->closingUuid;

        if ($uuid === null) {
            return;
        }

        $this->closedOverrides[$uuid] = true;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(FinanceService::class)->closeShift($uuid) ?? true, __('waqty.genericError'));
        }

        $this->showClose = false;
        $this->closingUuid = null;
        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('txn.shifts.closed'));
    }

    public function render()
    {
        return view('livewire.transactions.shifts');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        $today = Carbon::today();

        return [
            ['uuid' => 'S1', 'label' => 'وردية صباحية', 'cashier' => 'سارة أحمد', 'opened_at' => $today->copy()->setTime(8, 0)->toDateTimeString(), 'closed_at' => null, 'expected_total' => 32000, 'actual_total' => null, 'variance' => 0, 'status' => 'open'],
            ['uuid' => 'S2', 'label' => 'وردية مسائية', 'cashier' => 'منى عادل', 'opened_at' => $today->copy()->setTime(14, 0)->toDateTimeString(), 'closed_at' => null, 'expected_total' => 21000, 'actual_total' => null, 'variance' => 0, 'status' => 'open'],
            ['uuid' => 'S3', 'label' => 'وردية صباحية', 'cashier' => 'خالد حسن', 'opened_at' => $today->copy()->subDay()->setTime(8, 0)->toDateTimeString(), 'closed_at' => $today->copy()->subDay()->setTime(16, 0)->toDateTimeString(), 'expected_total' => 45000, 'actual_total' => 44500, 'variance' => -500, 'status' => 'closed'],
            ['uuid' => 'S4', 'label' => 'وردية مسائية', 'cashier' => 'ياسمين فاروق', 'opened_at' => $today->copy()->subDays(2)->setTime(14, 0)->toDateTimeString(), 'closed_at' => $today->copy()->subDays(2)->setTime(22, 0)->toDateTimeString(), 'expected_total' => 60000, 'actual_total' => 60000, 'variance' => 0, 'status' => 'closed'],
            ['uuid' => 'S5', 'label' => 'وردية صباحية', 'cashier' => 'طارق سامي', 'opened_at' => $today->copy()->subDays(3)->setTime(8, 0)->toDateTimeString(), 'closed_at' => $today->copy()->subDays(3)->setTime(16, 0)->toDateTimeString(), 'expected_total' => 38000, 'actual_total' => 39000, 'variance' => 1000, 'status' => 'closed'],
        ];
    }
}
