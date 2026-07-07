<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\BookingActivityData;
use App\Data\Waqty\BookingData;
use App\Enums\BookingStatus;
use App\Services\Waqty\BookingService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\BookingSamples;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Booking — Waqty')]
class BookingDetail extends Component
{
    use HandlesWaqtyErrors;

    public string $uuid = '';

    /** Optimistic status after a transition (keeps the demo responsive in fallback). */
    public ?string $statusOverride = null;

    public bool $showCancel = false;

    public string $cancelReason = '';

    private bool $fallbackUsed = false;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    #[Computed]
    public function booking(): BookingData
    {
        try {
            $b = app(BookingService::class)->get($this->uuid);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $b = BookingData::from(BookingSamples::one($this->uuid, Carbon::today()->toDateString()));
        }

        if ($this->statusOverride !== null) {
            $b->status = $this->statusOverride;
        }

        return $b;
    }

    /** @return array<int, BookingActivityData> */
    #[Computed]
    public function activities(): array
    {
        try {
            return app(BookingService::class)->activities($this->uuid);
        } catch (WaqtyApiException) {
            return array_map(fn ($a) => BookingActivityData::from($a), BookingSamples::activities());
        }
    }

    public function usingFallback(): bool
    {
        $this->booking();

        return $this->fallbackUsed;
    }

    public function changeStatus(string $to): void
    {
        $target = BookingStatus::tryFrom($to);
        if (! $target || ! $this->booking()->statusEnum()->canTransition($target)) {
            return;
        }

        if (! $this->usingFallback()) {
            $ok = $this->waqty(fn () => app(BookingService::class)->setStatus($this->uuid, $to) ?? true, __('waqty.genericError'));
            if (! $ok) {
                return;
            }
        }

        $this->statusOverride = $to;
        unset($this->booking, $this->activities);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function confirmCancel(): void
    {
        $this->cancelReason = '';
        $this->showCancel = true;
    }

    public function cancelBooking(): void
    {
        $reason = trim($this->cancelReason) ?: null;

        if (! $this->usingFallback()) {
            $ok = $this->waqty(fn () => app(BookingService::class)->cancel($this->uuid, $reason) ?? true, __('waqty.genericError'));
            if (! $ok) {
                $this->showCancel = false;

                return;
            }
        }

        $this->statusOverride = BookingStatus::Cancelled->value;
        $this->showCancel = false;
        unset($this->booking, $this->activities);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function render()
    {
        return view('livewire.bookings.detail');
    }
}
