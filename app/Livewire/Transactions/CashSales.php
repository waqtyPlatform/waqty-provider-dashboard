<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Cash Sales — Waqty')]
class CashSales extends Component
{
    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 8;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(FinanceService::class)->cashSales(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->mapRow((array) $r), array_values($rows));

        return $this->loaded;
    }

    /**
     * Normalise a raw cash-sale row into the shape the view renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function mapRow(array $r): array
    {
        $services = $r['services'] ?? $r['service'] ?? [];

        if (is_array($services)) {
            $services = implode('، ', array_values(array_filter(array_map(
                fn ($s) => is_array($s) ? (string) ($s['name'] ?? '') : (string) $s,
                $services,
            ))));
        }

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? $r['receipt_number'] ?? $r['reference_number'] ?? uniqid()),
            'receipt' => (string) ($r['receipt_number'] ?? $r['receipt_no'] ?? $r['reference_number'] ?? $r['invoice_number'] ?? ''),
            'created_at' => $r['created_at'] ?? $r['date'] ?? null,
            'client' => (string) ($r['client'] ?? data_get($r, 'client.name') ?? data_get($r, 'customer.name') ?? $r['client_name'] ?? ''),
            'services' => (string) $services,
            'employee' => (string) ($r['employee'] ?? data_get($r, 'employee.name') ?? $r['employee_name'] ?? data_get($r, 'cashier.name') ?? ''),
            'method' => (string) ($r['payment_method'] ?? $r['method'] ?? 'cash'),
            'amount' => (int) ($r['amount'] ?? $r['total'] ?? 0),
        ];
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

        if ($search === '') {
            return $this->source();
        }

        return array_values(array_filter($this->source(), fn (array $r) => str_contains(mb_strtolower((string) $r['client']), $search)
            || str_contains(mb_strtolower((string) $r['receipt']), $search)));
    }

    /** @return array<int, array<string, mixed>> */
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

    /** @return array{total:int, count:int, average:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $total = array_sum(array_map(fn (array $r) => $r['amount'], $all));
        $count = count($all);

        return [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? (int) round($total / $count) : 0,
        ];
    }

    /** Stream the filtered cash sales as a CSV download (UTF-8 BOM for Excel/Arabic). */
    public function exportCsv(): StreamedResponse
    {
        $rows = $this->filtered();
        $filename = 'cash-sales-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('txn.cash.thReceipt'),
                __('txn.thDateTime'),
                __('txn.thClient'),
                __('txn.cash.thServices'),
                __('txn.thEmployee'),
                __('txn.thMethod'),
                __('txn.thAmount'),
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['receipt'],
                    (string) $r['created_at'],
                    $r['client'],
                    $r['services'],
                    $r['employee'],
                    $r['method'],
                    Money::format($r['amount'], false),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.transactions.cash-sales');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'CS1', 'receipt_number' => 'REC-2041', 'created_at' => '2026-07-04 11:20:00', 'client' => 'فاطمة رشاد', 'services' => ['صبغة شعر', 'قص شعر'], 'employee' => 'سارة أحمد', 'payment_method' => 'cash', 'amount' => 32000],
            ['uuid' => 'CS2', 'receipt_number' => 'REC-2042', 'created_at' => '2026-07-04 10:05:00', 'client' => 'مريم سمير', 'services' => ['مانيكير', 'باديكير'], 'employee' => 'ياسمين فاروق', 'payment_method' => 'card', 'amount' => 18000],
            ['uuid' => 'CS3', 'receipt_number' => 'REC-2043', 'created_at' => '2026-07-03 16:40:00', 'client' => 'هدى كمال', 'services' => ['حمام كريم'], 'employee' => 'سارة أحمد', 'payment_method' => 'cash', 'amount' => 9000],
            ['uuid' => 'CS4', 'receipt_number' => 'REC-2044', 'created_at' => '2026-07-03 13:15:00', 'client' => 'نور الدين', 'services' => ['حلاقة ذقن', 'قص شعر'], 'employee' => 'خالد حسن', 'payment_method' => 'cash', 'amount' => 15000],
            ['uuid' => 'CS5', 'receipt_number' => 'REC-2045', 'created_at' => '2026-07-02 18:30:00', 'client' => 'سلمى إبراهيم', 'services' => ['بروتين شعر'], 'employee' => 'منى عادل', 'payment_method' => 'card', 'amount' => 75000],
            ['uuid' => 'CS6', 'receipt_number' => 'REC-2046', 'created_at' => '2026-07-02 12:00:00', 'client' => 'دعاء طارق', 'services' => ['تنظيف بشرة', 'ماسك مرطب'], 'employee' => 'ياسمين فاروق', 'payment_method' => 'cash', 'amount' => 26000],
        ];
    }
}
