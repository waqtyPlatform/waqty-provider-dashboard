<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Fingerprints — Waqty')]
class Fingerprints extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all'; // all | enrolled | not_enrolled

    // Enroll / re-enroll modal (simulated scan).
    public bool $showEnroll = false;

    public ?string $enrollUuid = null;

    public string $enrollEmployee = '';

    public bool $isReenroll = false;

    public string $fingersCount = '1';

    // Clear confirmation.
    public bool $showClear = false;

    public ?string $clearUuid = null;

    public string $clearEmployee = '';

    /**
     * Optimistic local overrides keyed by row uuid. Applied on top of the
     * source so enroll/clear reflect instantly while running under fallback.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $overrides = [];

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
            $rows = app(EmployeeHrService::class)->fingerprints();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        // Layer optimistic overrides recorded by enroll/clear actions.
        $this->loaded = array_map(function (array $row) {
            $uuid = (string) ($row['uuid'] ?? '');

            return isset($this->overrides[$uuid])
                ? array_merge($row, $this->overrides[$uuid])
                : $row;
        }, $rows);

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
        $search = trim(mb_strtolower($this->search));
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) ($r['employee'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($r['department'] ?? '')), $search);

            return $matchesSearch && ($status === 'all' || ($r['status'] ?? '') === $status);
        }));
    }

    /** @return array{enrolled:int, pending:int, fingers:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $enrolled = count(array_filter($all, fn (array $r) => ($r['status'] ?? '') === 'enrolled'));
        $fingers = array_sum(array_map(
            fn (array $r) => ($r['status'] ?? '') === 'enrolled' ? (int) ($r['fingers'] ?? 0) : 0,
            $all
        ));

        return [
            'enrolled' => $enrolled,
            'pending' => count($all) - $enrolled,
            'fingers' => $fingers,
        ];
    }

    /** Status enum values (English); labels resolve via emp.fingerprints.status*. @return array<int, string> */
    public function statuses(): array
    {
        return ['enrolled', 'not_enrolled'];
    }

    public function openEnroll(string $uuid): void
    {
        $row = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $row) {
            return;
        }

        $this->enrollUuid = $uuid;
        $this->enrollEmployee = (string) ($row['employee'] ?? '');
        $this->isReenroll = ($row['status'] ?? '') === 'enrolled';
        $this->fingersCount = (string) max(1, (int) ($row['fingers'] ?? 1));
        $this->resetValidation();
        $this->showEnroll = true;
        $this->dispatch('fp-scan-reset');
    }

    /** Called by the scan animation once the simulated read completes. */
    public function enrollFingerprint(): void
    {
        if (! $this->enrollUuid) {
            return;
        }

        $this->validate([
            'fingersCount' => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'fingersCount.required' => __('emp.fingerprints.fingersRequired'),
            'fingersCount.integer' => __('emp.fingerprints.fingersInteger'),
            'fingersCount.min' => __('emp.fingerprints.fingersRange'),
            'fingersCount.max' => __('emp.fingerprints.fingersRange'),
        ]);

        $uuid = $this->enrollUuid;
        $fingers = (int) $this->fingersCount;

        $ok = true;
        if (! $this->usingFallback()) {
            $ok = (bool) $this->waqty(function () use ($uuid, $fingers) {
                $service = app(EmployeeHrService::class);
                $payload = [
                    'employee' => $this->enrollEmployee,
                    'fingers' => $fingers,
                    'status' => 'enrolled',
                ];
                $this->isReenroll
                    ? $service->reenrollFingerprint($uuid, $payload)
                    : $service->enrollFingerprint($payload + ['employee_uuid' => $uuid]);

                return true;
            }, __('waqty.genericError'));
        }

        if (! $ok) {
            return;
        }

        $this->overrides[$uuid] = [
            'status' => 'enrolled',
            'fingers' => $fingers,
            'last_sync' => __('emp.fingerprints.syncedNow'),
        ];

        $message = $this->isReenroll
            ? __('emp.fingerprints.reenrolled')
            : __('emp.fingerprints.enrolled');

        $this->showEnroll = false;
        $this->enrollUuid = null;
        $this->loaded = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function confirmClear(string $uuid): void
    {
        $row = collect($this->source())->firstWhere('uuid', $uuid);
        $this->clearUuid = $uuid;
        $this->clearEmployee = (string) ($row['employee'] ?? '');
        $this->showClear = true;
    }

    public function clearFingerprint(): void
    {
        if (! $this->clearUuid) {
            return;
        }

        $uuid = $this->clearUuid;

        $ok = true;
        if (! $this->usingFallback()) {
            $ok = (bool) $this->waqty(
                fn () => app(EmployeeHrService::class)->clearFingerprint($uuid) ?? true,
                __('waqty.genericError')
            );
        }

        $this->showClear = false;
        $this->clearUuid = null;

        if (! $ok) {
            return;
        }

        $this->overrides[$uuid] = ['status' => 'not_enrolled', 'fingers' => 0, 'last_sync' => null];
        $this->loaded = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('emp.fingerprints.cleared'));
    }

    public function render()
    {
        return view('livewire.employees.fingerprints');
    }

    /** Local Arabic sample roster for graceful degradation. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'FP1', 'employee' => 'سارة أحمد', 'department' => 'قسم الشعر', 'status' => 'enrolled', 'fingers' => 2, 'last_sync' => 'منذ ساعتين'],
            ['uuid' => 'FP2', 'employee' => 'منى عادل', 'department' => 'قسم العناية بالبشرة', 'status' => 'enrolled', 'fingers' => 3, 'last_sync' => 'منذ يوم'],
            ['uuid' => 'FP3', 'employee' => 'خالد حسن', 'department' => 'الاستقبال', 'status' => 'not_enrolled', 'fingers' => 0, 'last_sync' => null],
            ['uuid' => 'FP4', 'employee' => 'ياسمين فاروق', 'department' => 'قسم الأظافر', 'status' => 'enrolled', 'fingers' => 1, 'last_sync' => 'منذ ٣ أيام'],
            ['uuid' => 'FP5', 'employee' => 'طارق سامي', 'department' => 'قسم المكياج', 'status' => 'not_enrolled', 'fingers' => 0, 'last_sync' => null],
        ];
    }
}
