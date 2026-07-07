<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\BusinessHoursData;
use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Working Hours — Waqty')]
class BusinessHours extends Component
{
    use HandlesWaqtyErrors;

    /** @var array<int, array{day:int, open_time:string, close_time:string, is_closed:bool}> */
    public array $days = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $fetched = app(SettingsService::class)->businessHours();
            $this->days = array_map(fn (BusinessHoursData $d) => [
                'day' => $d->day,
                'open_time' => substr((string) $d->open_time, 0, 5),
                'close_time' => substr((string) $d->close_time, 0, 5),
                'is_closed' => $d->is_closed,
            ], $fetched);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
        }

        if ($this->days === []) {
            $this->days = $this->defaultDays();
        }
    }

    public function toggleClosed(int $index): void
    {
        if (isset($this->days[$index])) {
            $this->days[$index]['is_closed'] = ! $this->days[$index]['is_closed'];
        }
    }

    public function save(): void
    {
        $this->validate([
            'days.*.open_time' => ['required', 'string'],
            'days.*.close_time' => ['required', 'string'],
        ]);

        if (! $this->fallbackUsed) {
            $this->waqty(fn () => app(SettingsService::class)->updateBusinessHours($this->days) ?? true, __('waqty.genericError'));
        }

        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    /** @return array<int, string> day-of-week label keyed by index 0=Sunday */
    public function dayLabels(): array
    {
        return [
            __('settings.hours.sunday'),
            __('settings.hours.monday'),
            __('settings.hours.tuesday'),
            __('settings.hours.wednesday'),
            __('settings.hours.thursday'),
            __('settings.hours.friday'),
            __('settings.hours.saturday'),
        ];
    }

    public function render()
    {
        return view('livewire.settings.business-hours');
    }

    /** @return array<int, array{day:int, open_time:string, close_time:string, is_closed:bool}> */
    private function defaultDays(): array
    {
        $days = [];
        for ($d = 0; $d < 7; $d++) {
            $days[] = [
                'day' => $d,
                'open_time' => '09:00',
                'close_time' => '20:00',
                'is_closed' => $d === 5, // Friday closed by default
            ];
        }

        return $days;
    }
}
