<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\ClientAccountData;
use App\Services\Waqty\ClientAccountService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Last Visits — Waqty')]
class LastVisits extends Component
{
    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 15;

    /** @var array<int, ClientAccountData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, ClientAccountData> Clients sorted by most-recent visit first. */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(ClientAccountService::class)->clients(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = array_map(fn ($a) => ClientAccountData::from($a), $this->fallbackData());
        }

        usort($rows, fn (ClientAccountData $a, ClientAccountData $b) => strcmp((string) $b->last_booking_date, (string) $a->last_booking_date));
        $this->loaded = $rows;

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

    /** @return array{total:int, recent:int, overdue:int, followUp:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $days = fn (ClientAccountData $c) => $c->daysSince();

        return [
            'total' => count($all),
            'recent' => count(array_filter($all, fn ($c) => $days($c) !== null && $days($c) <= 7)),
            'overdue' => count(array_filter($all, fn ($c) => $days($c) !== null && $days($c) > 30)),
            'followUp' => count(array_filter($all, fn (ClientAccountData $c) => $c->needsFollowUp())),
        ];
    }

    public function render()
    {
        return view('livewire.customers.last-visits');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        $today = now();

        return [
            ['uuid' => 'L001', 'name' => 'مريم عادل', 'email' => 'mariam@example.com', 'phone' => '01512345678', 'total_bookings' => 41, 'last_booking_date' => $today->copy()->subDays(2)->toDateString()],
            ['uuid' => 'L002', 'name' => 'هناء فتحي', 'email' => 'hana@example.com', 'phone' => '01555443322', 'total_bookings' => 33, 'last_booking_date' => $today->copy()->subDays(5)->toDateString()],
            ['uuid' => 'L003', 'name' => 'ليلى حسن', 'email' => 'layla@example.com', 'phone' => '01012345678', 'total_bookings' => 24, 'last_booking_date' => $today->copy()->subDays(11)->toDateString()],
            ['uuid' => 'L004', 'name' => 'سلمى إبراهيم', 'email' => 'salma@example.com', 'phone' => '01198765432', 'total_bookings' => 12, 'last_booking_date' => $today->copy()->subDays(19)->toDateString()],
            ['uuid' => 'L005', 'name' => 'عمر خالد', 'email' => 'omar@example.com', 'phone' => '01123456789', 'total_bookings' => 8, 'last_booking_date' => $today->copy()->subDays(34)->toDateString()],
            ['uuid' => 'L006', 'name' => 'كريم مصطفى', 'email' => 'karim@example.com', 'phone' => '01276543210', 'total_bookings' => 2, 'last_booking_date' => $today->copy()->subDays(63)->toDateString()],
            ['uuid' => 'L007', 'name' => 'طارق سامي', 'email' => 'tarek@example.com', 'phone' => '01033221144', 'total_bookings' => 0, 'last_booking_date' => null],
        ];
    }
}
