<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\BookingData;
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
#[Title('Bookings — Waqty')]
class BookingList extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 10;

    // Cancel confirmation
    public bool $showCancel = false;

    public ?string $cancellingUuid = null;

    public string $cancelReason = '';

    /** @var array<int, BookingData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, BookingData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $filters = ['per_page' => 100];
            if ($this->statusFilter !== 'all') {
                $filters['status'] = $this->statusFilter;
            }
            $this->loaded = app(BookingService::class)->list($filters);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(
                fn ($a) => BookingData::from($a),
                BookingSamples::forDate(Carbon::today()->toDateString()),
            );
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, BookingData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (BookingData $b) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower($b->clientName()), $search)
                || str_contains(mb_strtolower($b->serviceName()), $search)
                || str_contains(mb_strtolower((string) $b->clientPhone()), $search);

            // When the API already filtered by status the client filter is a no-op;
            // for fallback data we still honour it here.
            $matchesStatus = $status === 'all' || $b->status === $status;

            return $matchesSearch && $matchesStatus;
        }));
    }

    /** @return array<int, BookingData> */
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

    /** @return array{total:int, confirmed:int, completed:int, revenue:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $count = fn (string $s) => count(array_filter($all, fn (BookingData $b) => $b->status === $s));

        return [
            'total' => count($all),
            'confirmed' => $count('confirmed'),
            'completed' => $count('completed'),
            'revenue' => array_sum(array_map(fn (BookingData $b) => $b->status === 'cancelled' ? 0 : (int) $b->price, $all)),
        ];
    }

    public function confirmCancel(string $uuid): void
    {
        $this->cancellingUuid = $uuid;
        $this->cancelReason = '';
        $this->showCancel = true;
    }

    public function cancelBooking(): void
    {
        if (! $this->cancellingUuid) {
            return;
        }

        $uuid = $this->cancellingUuid;
        $reason = trim($this->cancelReason) ?: null;

        $result = $this->waqty(fn () => app(BookingService::class)->cancel($uuid, $reason) ?? true, __('waqty.genericError'));

        $this->showCancel = false;
        $this->cancellingUuid = null;

        if ($result) {
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function render()
    {
        return view('livewire.bookings.list');
    }
}
