<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Branch Management — Waqty')]
class BranchManagement extends Component
{
    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /**
     * Branch rosters shaped for the view. Falls back to local Arabic sample
     * data (branches each carrying their own staff array) when the API is down.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function branches(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->branches();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->normalize((array) $r), $rows);

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->branches();

        return $this->fallbackUsed;
    }

    /** @return array{branches:int, staff:int, avg:int|float} */
    #[Computed]
    public function kpis(): array
    {
        $branches = $this->branches();
        $totalBranches = count($branches);
        $totalStaff = array_sum(array_map(fn ($b) => (int) $b['headcount'], $branches));

        return [
            'branches' => $totalBranches,
            'staff' => $totalStaff,
            'avg' => $totalBranches > 0 ? round($totalStaff / $totalBranches, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.employees.branch-management');
    }

    /**
     * Shape a raw API/sample branch row into the roster columns this screen
     * renders, tolerating the few key spellings the API may use.
     *
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    private function normalize(array $b): array
    {
        $rawStaff = $b['staff'] ?? $b['employees'] ?? $b['members'] ?? [];

        $staff = array_map(function ($m) {
            $m = (array) $m;

            return [
                'name' => (string) ($m['name'] ?? $m['employee_name'] ?? ''),
                'position' => (string) ($m['position'] ?? $m['title'] ?? $m['role'] ?? ''),
                'active' => (bool) ($m['active'] ?? true),
            ];
        }, is_array($rawStaff) ? $rawStaff : []);

        return [
            'uuid' => (string) ($b['uuid'] ?? $b['id'] ?? ''),
            'name' => (string) ($b['name'] ?? ''),
            'area' => (string) ($b['area'] ?? $b['city'] ?? $b['address'] ?? ''),
            'staff' => $staff,
            'headcount' => count($staff),
        ];
    }

    /**
     * Arabic sample branches with small staff rosters for graceful degradation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'B1', 'name' => 'فرع وسط البلد', 'area' => 'القاهرة', 'staff' => [
                ['name' => 'د. سارة أحمد', 'position' => 'مديرة الفرع', 'active' => true],
                ['name' => 'خالد حسن', 'position' => 'مصفف شعر', 'active' => true],
                ['name' => 'منى عادل', 'position' => 'أخصائية تجميل', 'active' => true],
                ['name' => 'عمر نبيل', 'position' => 'موظف استقبال', 'active' => false],
            ]],
            ['uuid' => 'B2', 'name' => 'فرع مدينة نصر', 'area' => 'القاهرة', 'staff' => [
                ['name' => 'ياسمين فاروق', 'position' => 'مديرة الفرع', 'active' => true],
                ['name' => 'طارق سامي', 'position' => 'مصفف شعر', 'active' => true],
                ['name' => 'هالة محمود', 'position' => 'أخصائية بشرة', 'active' => true],
            ]],
            ['uuid' => 'B3', 'name' => 'فرع الإسكندرية', 'area' => 'الإسكندرية', 'staff' => [
                ['name' => 'كريم مصطفى', 'position' => 'مدير الفرع', 'active' => true],
                ['name' => 'سلمى إبراهيم', 'position' => 'فنية أظافر', 'active' => true],
                ['name' => 'نور حسام', 'position' => 'موظف استقبال', 'active' => false],
            ]],
        ];
    }
}
