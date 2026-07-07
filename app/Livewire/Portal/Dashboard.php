<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Services\Waqty\EmployeePortalService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employee Portal › Dashboard — today's bookings + attendance check-in/out.
 * Employee token surface (/api/employee/*). Falls back to sample data offline.
 */
#[Layout('components.layouts.employee')]
#[Title('My Day — Waqty')]
class Dashboard extends Component
{
    use HandlesWaqtyErrors;

    /** @var array<int, array<string, mixed>> */
    public array $bookings = [];

    public bool $checkedIn = false;

    public ?string $checkInTime = null;

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        $this->load();
    }

    private function load(): void
    {
        try {
            $service = app(EmployeePortalService::class);
            $this->bookings = $service->todayBookings();
            $today = now()->toDateString();
            $records = $service->attendance($today, $today);
            $record = $records[0] ?? null;
            $this->checkedIn = $record !== null && ! blank($record['check_in'] ?? null) && blank($record['check_out'] ?? null);
            $this->checkInTime = $record['check_in'] ?? null;
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loadFallback();
        }
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    public function checkIn(): void
    {
        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeePortalService::class)->checkIn() ?? true, __('waqty.genericError'));
        }
        $this->checkedIn = true;
        $this->checkInTime = now()->format('H:i');
        $this->dispatch('notify', type: 'success', message: __('portal.checkedIn'));
    }

    public function checkOut(): void
    {
        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeePortalService::class)->checkOut() ?? true, __('waqty.genericError'));
        }
        $this->checkedIn = false;
        $this->dispatch('notify', type: 'success', message: __('portal.checkedOut'));
    }

    /** @return array{total:int, done:int, upcoming:int} */
    public function stats(): array
    {
        $done = 0;
        foreach ($this->bookings as $b) {
            if (in_array($b['status'] ?? '', ['completed', 'done'], true)) {
                $done++;
            }
        }

        return ['total' => count($this->bookings), 'done' => $done, 'upcoming' => count($this->bookings) - $done];
    }

    public function render()
    {
        return view('livewire.portal.dashboard');
    }

    private function loadFallback(): void
    {
        $this->bookings = [
            ['uuid' => 'B1', 'start_time' => '09:30', 'service' => 'قصّة شعر كلاسيك', 'client' => 'أحمد مصطفى', 'status' => 'completed'],
            ['uuid' => 'B2', 'start_time' => '11:00', 'service' => 'صبغة شعر', 'client' => 'نور عادل', 'status' => 'confirmed'],
            ['uuid' => 'B3', 'start_time' => '13:15', 'service' => 'تهذيب اللحية', 'client' => 'كريم فؤاد', 'status' => 'confirmed'],
            ['uuid' => 'B4', 'start_time' => '15:00', 'service' => 'تدليك عميق للأنسجة', 'client' => 'سلمى طارق', 'status' => 'pending'],
        ];
        $this->checkedIn = true;
        $this->checkInTime = '09:02';
    }
}
