<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\EmployeeService;
use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settings › Service Employees — a services × employees assignment matrix.
 * Loads both lists on mount, seeds the grid from the existing mapping, and
 * PUTs the whole matrix in one call. Falls back to sample data when offline.
 */
#[Layout('components.layouts.app')]
#[Title('Service Employees — Waqty')]
class ServiceEmployees extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    /** @var array<int, array{uuid:string, name:string}> */
    public array $services = [];

    /** @var array<int, array{uuid:string, name:string}> */
    public array $employees = [];

    /** service_uuid => (employee_uuid => bool). @var array<string, array<string, bool>> */
    public array $assignments = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $services = app(ServiceCatalogService::class)->services([], false);
            $employees = app(EmployeeService::class)->employees();
            $existing = app(ServiceCatalogService::class)->serviceEmployees();

            $this->services = array_map(fn ($s) => ['uuid' => (string) $s->uuid, 'name' => (string) $s->name], $services);
            $this->employees = array_map(fn ($e) => ['uuid' => (string) $e->uuid, 'name' => (string) $e->name], $employees);
            $this->seedFromMappings($existing);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loadFallback();
        }

        if ($this->services === [] || $this->employees === []) {
            $this->fallbackUsed = true;
            $this->loadFallback();
        }
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    /** @param array<int, array<string, mixed>> $mappings */
    private function seedFromMappings(array $mappings): void
    {
        // Start with everything unassigned.
        foreach ($this->services as $s) {
            foreach ($this->employees as $e) {
                $this->assignments[$s['uuid']][$e['uuid']] = false;
            }
        }
        foreach ($mappings as $m) {
            $svc = $m['service_uuid'] ?? null;
            $emp = $m['employee_uuid'] ?? null;
            if ($svc !== null && $emp !== null && isset($this->assignments[$svc][$emp])) {
                $this->assignments[$svc][$emp] = (bool) ($m['active'] ?? true);
            }
        }
    }

    /** Toggle every employee for one service on/off from the row header. */
    public function toggleRow(string $serviceUuid): void
    {
        if (! isset($this->assignments[$serviceUuid])) {
            return;
        }
        $allOn = ! in_array(false, $this->assignments[$serviceUuid], true);
        foreach ($this->assignments[$serviceUuid] as $emp => $_) {
            $this->assignments[$serviceUuid][$emp] = ! $allOn;
        }
    }

    public function saveAll(): void
    {
        $mappings = [];
        foreach ($this->assignments as $svc => $emps) {
            foreach ($emps as $emp => $active) {
                $mappings[] = ['service_uuid' => $svc, 'employee_uuid' => $emp, 'active' => (bool) $active];
            }
        }

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ServiceCatalogService::class)->saveServiceEmployees($mappings) ?? true, __('waqty.genericError'));
        }

        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    /** @return array<int, array{uuid:string, name:string}> */
    public function filteredServices(): array
    {
        $q = trim(mb_strtolower($this->search));
        if ($q === '') {
            return $this->services;
        }

        return array_values(array_filter($this->services, fn ($s) => str_contains(mb_strtolower($s['name']), $q)));
    }

    public function render()
    {
        return view('livewire.settings.service-employees');
    }

    private function loadFallback(): void
    {
        $this->services = [
            ['uuid' => 'S1', 'name' => 'قصّة شعر كلاسيك'],
            ['uuid' => 'S2', 'name' => 'صبغة شعر'],
            ['uuid' => 'S3', 'name' => 'مانيكير'],
            ['uuid' => 'S4', 'name' => 'مساج الأنسجة العميقة'],
            ['uuid' => 'S5', 'name' => 'جلسة عناية بالبشرة'],
        ];
        $this->employees = [
            ['uuid' => 'E1', 'name' => 'سارة أحمد'],
            ['uuid' => 'E2', 'name' => 'عمر خالد'],
            ['uuid' => 'E3', 'name' => 'ليلى حسن'],
            ['uuid' => 'E4', 'name' => 'يوسف علي'],
        ];
        // A believable default: each employee handles a couple of services.
        $default = [
            'S1' => ['E1', 'E2', 'E4'],
            'S2' => ['E1', 'E3'],
            'S3' => ['E3'],
            'S4' => ['E2', 'E4'],
            'S5' => ['E1', 'E3'],
        ];
        $this->assignments = [];
        foreach ($this->services as $s) {
            foreach ($this->employees as $e) {
                $this->assignments[$s['uuid']][$e['uuid']] = in_array($e['uuid'], $default[$s['uuid']] ?? [], true);
            }
        }
    }
}
