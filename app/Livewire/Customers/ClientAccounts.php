<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\BookingData;
use App\Data\Waqty\ClientAccountData;
use App\Services\Waqty\ClientAccountService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\BookingSamples;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Client Accounts — Waqty')]
class ClientAccounts extends Component
{
    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 15;

    // Booking-history slide-over
    public bool $showHistory = false;

    public ?string $historyUuid = null;

    public string $historyName = '';

    /** @var array<int, ClientAccountData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, ClientAccountData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ClientAccountService::class)->clients(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ClientAccountData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ClientAccountData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));

        return array_values(array_filter($this->source(), function (ClientAccountData $c) use ($search) {
            return $search === ''
                || str_contains(mb_strtolower((string) $c->name), $search)
                || str_contains(mb_strtolower((string) $c->email), $search)
                || str_contains(mb_strtolower((string) $c->phone), $search);
        }));
    }

    /** @return array<int, ClientAccountData> */
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

    /** @return array{total:int, bookings:int, withBookings:int, avg:float} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $bookings = array_sum(array_map(fn (ClientAccountData $c) => $c->total_bookings, $all));

        return [
            'total' => count($all),
            'bookings' => $bookings,
            'withBookings' => count(array_filter($all, fn (ClientAccountData $c) => $c->total_bookings > 0)),
            'avg' => count($all) > 0 ? round($bookings / count($all), 1) : 0.0,
        ];
    }

    /** @return array<int, BookingData> */
    #[Computed]
    public function history(): array
    {
        if (! $this->historyUuid) {
            return [];
        }

        try {
            return app(ClientAccountService::class)->clientBookings($this->historyUuid, ['per_page' => 20]);
        } catch (WaqtyApiException) {
            return array_map(
                fn ($a) => BookingData::from($a),
                BookingSamples::forDate(now()->toDateString()),
            );
        }
    }

    public function openHistory(string $uuid): void
    {
        $client = collect($this->source())->firstWhere('uuid', $uuid);
        $this->historyUuid = $uuid;
        $this->historyName = $client?->name ?? '';
        unset($this->history);
        $this->showHistory = true;
    }

    public function render()
    {
        return view('livewire.customers.client-accounts');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'A001', 'name' => 'ليلى حسن', 'email' => 'layla@example.com', 'phone' => '01012345678', 'total_bookings' => 24, 'last_booking_date' => '2026-06-28'],
            ['uuid' => 'A002', 'name' => 'عمر خالد', 'email' => 'omar@example.com', 'phone' => '01123456789', 'total_bookings' => 8, 'last_booking_date' => '2026-06-15'],
            ['uuid' => 'A003', 'name' => 'مريم عادل', 'email' => 'mariam@example.com', 'phone' => '01512345678', 'total_bookings' => 41, 'last_booking_date' => '2026-07-01'],
            ['uuid' => 'A004', 'name' => 'يوسف علي', 'email' => 'youssef@example.com', 'phone' => '01087654321', 'total_bookings' => 5, 'last_booking_date' => '2026-05-20'],
            ['uuid' => 'A005', 'name' => 'سلمى إبراهيم', 'email' => 'salma@example.com', 'phone' => '01198765432', 'total_bookings' => 12, 'last_booking_date' => '2026-06-22'],
            ['uuid' => 'A006', 'name' => 'كريم مصطفى', 'email' => 'karim@example.com', 'phone' => '01276543210', 'total_bookings' => 2, 'last_booking_date' => '2026-04-10'],
            ['uuid' => 'A007', 'name' => 'هناء فتحي', 'email' => 'hana@example.com', 'phone' => '01555443322', 'total_bookings' => 33, 'last_booking_date' => '2026-06-30'],
            ['uuid' => 'A008', 'name' => 'طارق سامي', 'email' => 'tarek@example.com', 'phone' => '01033221144', 'total_bookings' => 0, 'last_booking_date' => null],
        ];
    }
}
