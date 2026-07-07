<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\EmployeeData;
use App\Data\Waqty\ServiceData;
use App\Services\Waqty\BookingService;
use App\Services\Waqty\EmployeeService;
use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\BookingSamples;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('New Booking — Waqty')]
class NewBooking extends Component
{
    use HandlesWaqtyErrors;

    #[Url]
    public string $date = '';

    #[Url]
    public string $time = '';

    #[Url]
    public string $emp = '';

    public string $form_service = '';

    public string $form_employee = '';

    public string $form_date = '';

    public string $form_time = '';

    public string $form_client_name = '';

    public string $form_client_phone = '';

    public string $form_notes = '';

    private bool $fallbackUsed = false;

    public function mount(): void
    {
        $this->form_date = $this->date !== '' ? $this->date : Carbon::today()->toDateString();
        $this->form_time = $this->time !== '' ? $this->time : '09:00';
        $this->form_employee = $this->emp;
    }

    /** @return array<int, ServiceData> */
    #[Computed]
    public function services(): array
    {
        try {
            return array_values(array_filter(
                app(ServiceCatalogService::class)->services(),
                fn (ServiceData $s) => $s->active,
            ));
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return array_map(fn ($a) => ServiceData::from($a), $this->fallbackServices());
        }
    }

    /** @return array<int, EmployeeData> */
    #[Computed]
    public function employees(): array
    {
        try {
            return array_values(array_filter(
                app(EmployeeService::class)->employees(),
                fn (EmployeeData $e) => $e->active && ! $e->blocked,
            ));
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return array_map(fn ($a) => EmployeeData::from($a), BookingSamples::employees());
        }
    }

    /** @return array<int, string> time options 09:00 → 20:30 */
    public function timeOptions(): array
    {
        $slots = [];
        for ($m = 9 * 60; $m <= 20 * 60 + 30; $m += 30) {
            $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $slots;
    }

    public function save(): void
    {
        $this->validate([
            'form_service' => ['required', 'string'],
            'form_employee' => ['nullable', 'string'],
            'form_date' => ['required', 'date'],
            'form_time' => ['required', 'string'],
            'form_client_name' => ['required', 'string', 'max:100'],
            'form_client_phone' => ['nullable', 'string', 'min:8', 'max:20'],
            'form_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = array_filter([
            'service_uuid' => $this->form_service,
            'employee_uuid' => $this->form_employee ?: null,
            'booking_date' => $this->form_date,
            'start_time' => $this->form_time,
            'user_name' => trim($this->form_client_name),
            'user_phone' => trim($this->form_client_phone) ?: null,
            'notes' => trim($this->form_notes) ?: null,
        ], fn ($v) => $v !== null);

        // Touch the computeds so fallback state is known before deciding.
        $this->services();

        if ($this->fallbackUsed) {
            // No live API — acknowledge and return to the calendar (demo behaviour).
            session()->flash('notify', __('common.saved'));
            $this->redirect(route('bookings', ['date' => $this->form_date]), navigate: true);

            return;
        }

        $result = $this->waqty(fn () => app(BookingService::class)->create($payload), __('waqty.genericError'));

        if ($result) {
            session()->flash('notify', __('common.saved'));
            $this->redirect(route('bookings', ['date' => $this->form_date]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.bookings.new');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackServices(): array
    {
        return [
            ['uuid' => 'S001', 'name' => 'قصّة شعر كلاسيك', 'estimated_duration_minutes' => 30, 'active' => true, 'price' => 15000],
            ['uuid' => 'S003', 'name' => 'صبغة شعر', 'estimated_duration_minutes' => 90, 'active' => true, 'price' => 45000],
            ['uuid' => 'S006', 'name' => 'مساج الأنسجة العميقة', 'estimated_duration_minutes' => 60, 'active' => true, 'price' => 55000],
            ['uuid' => 'S009', 'name' => 'مكياج عرائس', 'estimated_duration_minutes' => 120, 'active' => true, 'price' => 150000],
            ['uuid' => 'S010', 'name' => 'أظافر جل', 'estimated_duration_minutes' => 60, 'active' => true, 'price' => 30000],
        ];
    }
}
