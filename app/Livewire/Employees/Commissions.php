<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Commissions — Waqty')]
class Commissions extends Component
{
    use HandlesWaqtyErrors;

    /** Active breakdown tab: by-service | by-segment | targets | recalc. */
    #[Url]
    public string $tab = 'by-service';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $employeeFilter = 'all';

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->commissions([
                'from' => $this->dateFrom ?: null,
                'to' => $this->dateTo ?: null,
                'per_page' => 100,
            ]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        return $this->loaded = array_map(fn ($r) => $this->normalize($r), $rows);
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /**
     * Rows for the active tab, narrowed by the employee and date-range filters.
     * The recalc tab previews every line the recalculation will touch.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): array
    {
        $tab = $this->tab;
        $employee = $this->employeeFilter;
        $from = $this->dateFrom;
        $to = $this->dateTo;

        return array_values(array_filter($this->source(), function (array $r) use ($tab, $employee, $from, $to) {
            $matchesTab = $tab === 'recalc' || $r['tab'] === $tab;
            $matchesEmployee = $employee === 'all' || $r['employee'] === $employee;
            $matchesDate = ($from === '' || $r['date'] >= $from) && ($to === '' || $r['date'] <= $to);

            return $matchesTab && $matchesEmployee && $matchesDate;
        }));
    }

    /** @return array{total:int, topEarner:string, count:int} */
    #[Computed]
    public function kpis(): array
    {
        $rows = $this->rows();

        $byEmployee = [];
        foreach ($rows as $r) {
            $byEmployee[$r['employee']] = ($byEmployee[$r['employee']] ?? 0) + (int) $r['commission'];
        }
        arsort($byEmployee);

        return [
            'total' => array_sum(array_map(fn ($r) => (int) $r['commission'], $rows)),
            'topEarner' => $byEmployee === [] ? '—' : (string) array_key_first($byEmployee),
            'count' => count($rows),
        ];
    }

    /** Distinct employee names for the filter <select>. @return array<int, string> */
    public function employeeOptions(): array
    {
        return array_values(array_unique(array_map(fn ($r) => (string) $r['employee'], $this->source())));
    }

    /** Recalculate commissions for the selected range, then notify. */
    public function calculate(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ], [
            'dateFrom.required' => __('emp.commissions.dateFromRequired'),
            'dateTo.required' => __('emp.commissions.dateToRequired'),
            'dateTo.after_or_equal' => __('emp.commissions.dateToAfter'),
        ]);

        $payload = [
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
            'employee' => $this->employeeFilter === 'all' ? null : $this->employeeFilter,
        ];

        $result = $this->waqty(
            fn () => app(EmployeeHrService::class)->calculateCommissions($payload) ?? true,
            __('emp.commissions.calcFailed')
        );

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->rows, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('emp.commissions.calcDone'));
        }
    }

    public function export(): void
    {
        $this->dispatch('notify', type: 'info', message: __('emp.commissions.exportStarted'));
    }

    public function sendToPayroll(): void
    {
        $this->dispatch('notify', type: 'success', message: __('emp.commissions.sentToPayroll'));
    }

    public function render()
    {
        return view('livewire.employees.commissions');
    }

    /**
     * Shape a raw API/sample row into the columns this screen renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $base = (int) ($r['base'] ?? $r['base_amount'] ?? 0);
        $rate = round((float) ($r['rate'] ?? $r['rate_percent'] ?? 0), 1);
        $tab = (string) ($r['tab'] ?? $r['basis'] ?? 'by-service');

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'tab' => in_array($tab, ['by-service', 'by-segment', 'targets'], true) ? $tab : 'by-service',
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? ''),
            'subject' => (string) ($r['subject'] ?? $r['service'] ?? $r['segment'] ?? $r['target'] ?? ''),
            'base' => $base,
            'rate' => $rate,
            'commission' => (int) ($r['commission'] ?? round($base * $rate / 100)),
            'date' => (string) ($r['date'] ?? ''),
        ];
    }

    /**
     * Local Arabic sample commissions for graceful degradation, spanning the
     * three breakdowns the tabs surface.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            // By service
            ['uuid' => 'C1', 'tab' => 'by-service', 'employee' => 'سارة أحمد', 'service' => 'صبغة شعر', 'base' => 450000, 'rate' => 15, 'commission' => 67500, 'date' => '2026-07-05'],
            ['uuid' => 'C2', 'tab' => 'by-service', 'employee' => 'منى عادل', 'service' => 'قص وتصفيف', 'base' => 300000, 'rate' => 12, 'commission' => 36000, 'date' => '2026-07-05'],
            ['uuid' => 'C3', 'tab' => 'by-service', 'employee' => 'خالد حسن', 'service' => 'حلاقة رجالي', 'base' => 200000, 'rate' => 10, 'commission' => 20000, 'date' => '2026-07-04'],
            ['uuid' => 'C4', 'tab' => 'by-service', 'employee' => 'ياسمين فاروق', 'service' => 'مانيكير وباديكير', 'base' => 250000, 'rate' => 12, 'commission' => 30000, 'date' => '2026-07-03'],
            ['uuid' => 'C5', 'tab' => 'by-service', 'employee' => 'طارق سامي', 'service' => 'علاج بالكيراتين', 'base' => 600000, 'rate' => 18, 'commission' => 108000, 'date' => '2026-07-02'],
            ['uuid' => 'C6', 'tab' => 'by-service', 'employee' => 'عمر نبيل', 'service' => 'عناية بالبشرة', 'base' => 350000, 'rate' => 10, 'commission' => 35000, 'date' => '2026-07-01'],
            // By segment
            ['uuid' => 'C7', 'tab' => 'by-segment', 'employee' => 'سارة أحمد', 'segment' => 'عملاء VIP', 'base' => 800000, 'rate' => 20, 'commission' => 160000, 'date' => '2026-07-05'],
            ['uuid' => 'C8', 'tab' => 'by-segment', 'employee' => 'منى عادل', 'segment' => 'عملاء جدد', 'base' => 400000, 'rate' => 10, 'commission' => 40000, 'date' => '2026-07-04'],
            ['uuid' => 'C9', 'tab' => 'by-segment', 'employee' => 'خالد حسن', 'segment' => 'عملاء منتظمون', 'base' => 500000, 'rate' => 12, 'commission' => 60000, 'date' => '2026-07-03'],
            ['uuid' => 'C10', 'tab' => 'by-segment', 'employee' => 'ياسمين فاروق', 'segment' => 'عملاء VIP', 'base' => 350000, 'rate' => 20, 'commission' => 70000, 'date' => '2026-07-02'],
            // Targets
            ['uuid' => 'C11', 'tab' => 'targets', 'employee' => 'سارة أحمد', 'target' => 'تجاوز الهدف الشهري', 'base' => 3450000, 'rate' => 5, 'commission' => 172500, 'date' => '2026-07-05'],
            ['uuid' => 'C12', 'tab' => 'targets', 'employee' => 'منى عادل', 'target' => 'تحقيق 80% من الهدف', 'base' => 1600000, 'rate' => 3, 'commission' => 48000, 'date' => '2026-07-04'],
            ['uuid' => 'C13', 'tab' => 'targets', 'employee' => 'طارق سامي', 'target' => 'هدف ربع سنوي', 'base' => 3900000, 'rate' => 5, 'commission' => 195000, 'date' => '2026-07-02'],
        ];
    }
}
