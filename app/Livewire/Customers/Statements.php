<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\ClientStatementRowData;
use App\Services\Waqty\ClientAccountService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Statements — Waqty')]
class Statements extends Component
{
    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 12;

    /** @var array<int, ClientStatementRowData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, ClientStatementRowData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ClientAccountService::class)->statements(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ClientStatementRowData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ClientStatementRowData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));

        return array_values(array_filter($this->source(), function (ClientStatementRowData $r) use ($search) {
            return $search === ''
                || str_contains(mb_strtolower((string) $r->name), $search)
                || str_contains(mb_strtolower((string) $r->email), $search)
                || str_contains(mb_strtolower((string) $r->phone), $search);
        }));
    }

    /** @return array<int, ClientStatementRowData> */
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

    /** @return array{charged:int, paid:int, outstanding:int, clients:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'charged' => array_sum(array_map(fn (ClientStatementRowData $r) => $r->total_charged, $all)),
            'paid' => array_sum(array_map(fn (ClientStatementRowData $r) => $r->total_paid, $all)),
            'outstanding' => array_sum(array_map(fn (ClientStatementRowData $r) => $r->outstanding, $all)),
            'clients' => count($all),
        ];
    }

    public function render()
    {
        return view('livewire.customers.statements');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'S001', 'name' => 'ليلى حسن', 'email' => 'layla@example.com', 'phone' => '01012345678', 'total_bookings' => 24, 'completed_bookings' => 22, 'cancelled_bookings' => 2, 'total_charged' => 1850000, 'total_paid' => 1800000, 'outstanding' => 50000, 'last_booking_date' => '2026-06-28'],
            ['uuid' => 'S002', 'name' => 'عمر خالد', 'email' => 'omar@example.com', 'phone' => '01123456789', 'total_bookings' => 8, 'completed_bookings' => 8, 'cancelled_bookings' => 0, 'total_charged' => 420000, 'total_paid' => 420000, 'outstanding' => 0, 'last_booking_date' => '2026-06-15'],
            ['uuid' => 'S003', 'name' => 'مريم عادل', 'email' => 'mariam@example.com', 'phone' => '01512345678', 'total_bookings' => 41, 'completed_bookings' => 39, 'cancelled_bookings' => 2, 'total_charged' => 3120000, 'total_paid' => 2970000, 'outstanding' => 150000, 'last_booking_date' => '2026-07-01'],
            ['uuid' => 'S004', 'name' => 'يوسف علي', 'email' => 'youssef@example.com', 'phone' => '01087654321', 'total_bookings' => 5, 'completed_bookings' => 5, 'cancelled_bookings' => 0, 'total_charged' => 210000, 'total_paid' => 175000, 'outstanding' => 35000, 'last_booking_date' => '2026-05-20'],
            ['uuid' => 'S005', 'name' => 'سلمى إبراهيم', 'email' => 'salma@example.com', 'phone' => '01198765432', 'total_bookings' => 12, 'completed_bookings' => 11, 'cancelled_bookings' => 1, 'total_charged' => 680000, 'total_paid' => 680000, 'outstanding' => 0, 'last_booking_date' => '2026-06-22'],
        ];
    }
}
