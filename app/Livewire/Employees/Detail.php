<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Data\Waqty\EmployeeData;
use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Employees › Employee detail — a tabbed, mostly read-only profile.
 *
 * The header + overview come from EmployeeHrService->employee() (with an Arabic
 * sample fallback when the API is down); Schedule, Performance, Services &
 * commission and Activity are local mock summaries for preview. The Edit button
 * opens a light slide-over stub that only notifies (no server write here).
 */
#[Layout('components.layouts.app')]
#[Title('Employee — Waqty')]
class Detail extends Component
{
    use HandlesWaqtyErrors;

    public string $uuid = '';

    #[Url]
    public string $tab = 'overview';

    // Edit slide-over stub
    public bool $showEdit = false;

    public string $form_name = '';

    public string $form_position = '';

    public string $form_note = '';

    /** Raw employee row memo for the current request. @var array<string, mixed>|null */
    private ?array $raw = null;

    private bool $fallbackUsed = false;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /** Fetch the employee once per request, degrading to Arabic sample data. @return array<string, mixed> */
    private function data(): array
    {
        if ($this->raw !== null) {
            return $this->raw;
        }

        try {
            $row = app(EmployeeHrService::class)->employee($this->uuid);
        } catch (WaqtyApiException) {
            $row = [];
        }

        if ($row === []) {
            $this->fallbackUsed = true;
            $row = $this->fallbackEmployee();
        }

        return $this->raw = $row;
    }

    #[Computed]
    public function employee(): EmployeeData
    {
        return EmployeeData::from($this->data());
    }

    public function usingFallback(): bool
    {
        $this->data();

        return $this->fallbackUsed;
    }

    /** Overview KPIs; reads live figures when present, otherwise sample numbers. @return array<string, int|float> */
    #[Computed]
    public function overview(): array
    {
        $d = $this->data();

        return [
            'bookings' => (int) ($d['bookings'] ?? $d['bookings_count'] ?? 0),
            'revenue' => (int) ($d['revenue'] ?? 0),
            'rating' => round((float) ($d['rating'] ?? 0), 1),
            'clients' => (int) ($d['clients'] ?? $d['clients_served'] ?? 0),
        ];
    }

    /** MOCK weekly schedule summary. @return array{days: array<int, array<string, mixed>>, daysPerWeek: int, hoursPerWeek: int} */
    #[Computed]
    public function schedule(): array
    {
        $days = [
            ['key' => 'Sun', 'from' => '09:00', 'to' => '17:00'],
            ['key' => 'Mon', 'from' => '09:00', 'to' => '17:00'],
            ['key' => 'Tue', 'from' => '09:00', 'to' => '17:00'],
            ['key' => 'Wed', 'from' => '12:00', 'to' => '20:00'],
            ['key' => 'Thu', 'from' => '09:00', 'to' => '17:00'],
            ['key' => 'Fri', 'from' => null, 'to' => null],
            ['key' => 'Sat', 'from' => '10:00', 'to' => '15:00'],
        ];

        $working = array_filter($days, fn ($d) => $d['from'] !== null);

        return [
            'days' => $days,
            'daysPerWeek' => count($working),
            'hoursPerWeek' => 45,
        ];
    }

    /** MOCK performance metrics + monthly target. @return array<string, mixed> */
    #[Computed]
    public function performanceMetrics(): array
    {
        return [
            'tiles' => [
                ['label' => __('emp.detail.perfUtilization'), 'value' => '88%'],
                ['label' => __('emp.detail.perfRebooking'), 'value' => '64%'],
                ['label' => __('emp.detail.perfAvgService'), 'value' => '45 '.__('emp.detail.minutesUnit')],
                ['label' => __('emp.detail.perfNoShow'), 'value' => '6%'],
            ],
            'targetAchieved' => 8450000,
            'targetGoal' => 10000000,
        ];
    }

    /** MOCK services the employee performs, with commission rate + 30-day earnings. @return array<int, array<string, mixed>> */
    #[Computed]
    public function commissionServices(): array
    {
        return [
            ['name' => 'قص وتصفيف', 'price' => 18000, 'rate' => 15, 'earned' => 67500],
            ['name' => 'صبغة شعر', 'price' => 45000, 'rate' => 20, 'earned' => 180000],
            ['name' => 'علاج بالكيراتين', 'price' => 90000, 'rate' => 25, 'earned' => 225000],
            ['name' => 'مانيكير وباديكير', 'price' => 20000, 'rate' => 10, 'earned' => 40000],
        ];
    }

    /** MOCK recent activity feed. @return array<int, array<string, string>> */
    #[Computed]
    public function activity(): array
    {
        return [
            ['icon' => 'calendar-check', 'text' => 'أكملت حجزًا لـ ليلى حسن — صبغة شعر', 'at' => '2026-07-06 14:20:00'],
            ['icon' => 'star', 'text' => 'حصلت على تقييم 5 نجوم من نور علي', 'at' => '2026-07-05 11:05:00'],
            ['icon' => 'clock', 'text' => 'سجّلت الحضور الساعة 08:55 صباحًا', 'at' => '2026-07-05 08:55:00'],
            ['icon' => 'wallet', 'text' => 'احتُسبت عمولة بقيمة 512 جنيه', 'at' => '2026-07-01 18:00:00'],
            ['icon' => 'user-plus', 'text' => 'أضافت عميلة جديدة — سلمى مجدي', 'at' => '2026-06-28 16:30:00'],
        ];
    }

    public function openEdit(): void
    {
        $e = $this->employee();
        $this->form_name = (string) $e->name;
        $this->form_position = (string) $e->position;
        $this->form_note = '';
        $this->resetValidation();
        $this->showEdit = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:100'],
            'form_position' => ['nullable', 'string', 'max:100'],
            'form_note' => ['nullable', 'string', 'max:500'],
        ], [
            'form_name.required' => __('emp.detail.nameRequired'),
        ]);

        // Preview-only stub: nothing is written to the server here.
        $this->showEdit = false;
        $this->dispatch('notify', type: 'success', message: __('emp.detail.savedToast'));
    }

    public function render()
    {
        return view('livewire.employees.detail');
    }

    /** Local Arabic sample employee for graceful degradation. @return array<string, mixed> */
    private function fallbackEmployee(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => 'د. سارة أحمد',
            'email' => 'sara@waqty.com',
            'phone' => '01012345678',
            'branch_uuid' => 'B1',
            'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'],
            'active' => true,
            'blocked' => false,
            'role' => 'manager',
            'position' => 'أخصائية عناية بالبشرة',
            'rating' => 4.8,
            'created_at' => '2023-03-15',
            // Overview enrichments (non-canonical; used for the KPI strip).
            'bookings' => 142,
            'revenue' => 8450000,
            'clients' => 96,
        ];
    }
}
