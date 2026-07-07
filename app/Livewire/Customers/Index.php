<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\CustomerData;
use App\Services\Waqty\CustomerService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Clients — Waqty')]
class Index extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $groupFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    // Create/edit slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_phone = '';

    public string $form_email = '';

    public string $form_group = 'Regular';

    public string $form_notes = '';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, CustomerData>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedGroupFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, CustomerData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(CustomerService::class)->customers();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => CustomerData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, CustomerData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $group = $this->groupFilter;

        return array_values(array_filter($this->source(), function (CustomerData $c) use ($search, $group) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $c->name), $search)
                || str_contains(mb_strtolower((string) $c->phone), $search)
                || str_contains(mb_strtolower((string) $c->email), $search);

            $matchesGroup = $group === 'all'
                || ($group === 'vip' && $c->vip)
                || mb_strtolower($c->groupName()) === $group;

            return $matchesSearch && $matchesGroup;
        }));
    }

    /** @return array<int, CustomerData> */
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

    /** @return array{total:int, vip:int, new:int, inactive:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'total' => count($all),
            'vip' => count(array_filter($all, fn (CustomerData $c) => $c->vip)),
            'new' => count(array_filter($all, fn (CustomerData $c) => mb_strtolower($c->groupName()) === 'new')),
            'inactive' => count(array_filter($all, fn (CustomerData $c) => blank($c->last_visit))),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_phone', 'form_email', 'form_notes']);
        $this->form_group = 'Regular';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $customer = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $customer) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_name = (string) $customer->name;
        $this->form_phone = (string) $customer->phone;
        $this->form_email = (string) $customer->email;
        $this->form_group = $customer->groupName();
        $this->form_notes = (string) $customer->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:100'],
            'form_phone' => ['nullable', 'string', 'max:20'],
            'form_email' => ['nullable', 'email'],
            'form_group' => ['required', 'string'],
            'form_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'phone' => trim($this->form_phone) ?: null,
            'email' => trim($this->form_email) ?: null,
            'group' => $this->form_group,
            'notes' => trim($this->form_notes) ?: null,
        ];

        $service = app(CustomerService::class);

        $result = $this->waqty(function () use ($service, $payload) {
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result) {
            $this->showForm = false;
            $this->dispatch('notify', type: 'success', message: $this->editingUuid ? __('customers.toastUpdated') : __('customers.toastCreated'));
            $this->editingUuid = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteCustomer(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $service = app(CustomerService::class);
        $uuid = $this->deletingUuid;

        $result = $this->waqty(fn () => $service->delete($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result) {
            $this->dispatch('notify', type: 'success', message: __('customers.toastDeleted'));
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function render()
    {
        return view('livewire.customers.index');
    }

    /** Sample data mirroring the source FALLBACK_CLIENTS for graceful degradation. */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'C001', 'name' => 'ليلى حسن', 'email' => 'layla@example.com', 'phone' => '01012345678', 'vip' => true, 'group' => ['name' => 'VIP', 'color' => '#f59e0b'], 'total_visits' => 24, 'total_spent' => 1850000, 'last_visit' => '2026-06-28', 'allergies' => 'البنسلين'],
            ['uuid' => 'C002', 'name' => 'عمر خالد', 'email' => 'omar@example.com', 'phone' => '01123456789', 'vip' => false, 'group' => ['name' => 'عادي'], 'total_visits' => 8, 'total_spent' => 420000, 'last_visit' => '2026-06-15'],
            ['uuid' => 'C003', 'name' => 'نور الدين', 'email' => 'nour@example.com', 'phone' => '01234567890', 'vip' => false, 'group' => ['name' => 'جديد'], 'total_visits' => 1, 'total_spent' => 35000, 'last_visit' => null],
            ['uuid' => 'C004', 'name' => 'مريم عادل', 'email' => 'mariam@example.com', 'phone' => '01512345678', 'vip' => true, 'group' => ['name' => 'VIP', 'color' => '#f59e0b'], 'total_visits' => 41, 'total_spent' => 3120000, 'last_visit' => '2026-07-01'],
            ['uuid' => 'C005', 'name' => 'يوسف علي', 'email' => null, 'phone' => '01087654321', 'vip' => false, 'group' => ['name' => 'عادي'], 'total_visits' => 5, 'total_spent' => 210000, 'last_visit' => '2026-05-20'],
            ['uuid' => 'C006', 'name' => 'سلمى إبراهيم', 'email' => 'salma@example.com', 'phone' => '01198765432', 'vip' => false, 'group' => ['name' => 'عادي'], 'total_visits' => 12, 'total_spent' => 680000, 'last_visit' => '2026-06-22'],
            ['uuid' => 'C007', 'name' => 'كريم مصطفى', 'email' => 'karim@example.com', 'phone' => '01276543210', 'vip' => false, 'group' => ['name' => 'جديد'], 'total_visits' => 2, 'total_spent' => 90000, 'last_visit' => null],
            ['uuid' => 'C008', 'name' => 'هناء فتحي', 'email' => 'hana@example.com', 'phone' => '01555443322', 'vip' => true, 'group' => ['name' => 'VIP', 'color' => '#f59e0b'], 'total_visits' => 33, 'total_spent' => 2450000, 'last_visit' => '2026-06-30'],
            ['uuid' => 'C009', 'name' => 'طارق سامي', 'email' => 'tarek@example.com', 'phone' => '01033221144', 'vip' => false, 'group' => ['name' => 'عادي'], 'total_visits' => 7, 'total_spent' => 310000, 'last_visit' => '2026-06-10'],
        ];
    }
}
