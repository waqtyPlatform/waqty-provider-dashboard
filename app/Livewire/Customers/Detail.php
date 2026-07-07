<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\CustomerData;
use App\Data\Waqty\CustomerReviewData;
use App\Data\Waqty\CustomerStatementData;
use App\Data\Waqty\StaffNoteData;
use App\Services\Waqty\CustomerService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\CurrentProvider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Client — Waqty')]
class Detail extends Component
{
    use HandlesWaqtyErrors;

    public string $uuid = '';

    #[Url]
    public string $tab = 'overview';

    // Edit medical / notes slide-over
    public bool $showEdit = false;

    public string $form_allergies = '';

    public string $form_conditions = '';

    public string $form_medications = '';

    public string $form_notes = '';

    // Add staff note
    public string $noteText = '';

    /** Optimistically-added staff notes (demo-friendly when the API is down). @var array<int, array<string,mixed>> */
    public array $addedNotes = [];

    private bool $fallbackUsed = false;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    #[Computed]
    public function customer(): CustomerData
    {
        try {
            return app(CustomerService::class)->customer($this->uuid);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return CustomerData::from($this->fallbackCustomer());
        }
    }

    /** @return array<int, CustomerStatementData> */
    #[Computed]
    public function statements(): array
    {
        try {
            return app(CustomerService::class)->statements($this->uuid);
        } catch (WaqtyApiException) {
            return array_map(fn ($a) => CustomerStatementData::from($a), $this->fallbackStatements());
        }
    }

    /** @return array<int, CustomerReviewData> */
    #[Computed]
    public function reviews(): array
    {
        try {
            return app(CustomerService::class)->reviews($this->uuid);
        } catch (WaqtyApiException) {
            return array_map(fn ($a) => CustomerReviewData::from($a), $this->fallbackReviews());
        }
    }

    /** @return array<int, StaffNoteData> */
    #[Computed]
    public function staffNotes(): array
    {
        try {
            $notes = app(CustomerService::class)->staffNotes($this->uuid);
        } catch (WaqtyApiException) {
            $notes = array_map(fn ($a) => StaffNoteData::from($a), $this->fallbackNotes());
        }

        $optimistic = array_map(fn ($a) => StaffNoteData::from($a), $this->addedNotes);

        return [...$optimistic, ...$notes];
    }

    public function usingFallback(): bool
    {
        $this->customer();

        return $this->fallbackUsed;
    }

    public function openEdit(): void
    {
        $c = $this->customer();
        $this->form_allergies = (string) $c->allergies;
        $this->form_conditions = (string) $c->medical_conditions;
        $this->form_medications = (string) $c->medications;
        $this->form_notes = (string) $c->notes;
        $this->resetValidation();
        $this->showEdit = true;
    }

    public function saveMedical(): void
    {
        $this->validate([
            'form_allergies' => ['nullable', 'string', 'max:255'],
            'form_conditions' => ['nullable', 'string', 'max:255'],
            'form_medications' => ['nullable', 'string', 'max:255'],
            'form_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'allergies' => trim($this->form_allergies) ?: null,
            'medical_conditions' => trim($this->form_conditions) ?: null,
            'medications' => trim($this->form_medications) ?: null,
            'notes' => trim($this->form_notes) ?: null,
        ];

        if (! $this->usingFallback()) {
            $ok = $this->waqty(fn () => app(CustomerService::class)->update($this->uuid, $payload), __('waqty.genericError'));
            if (! $ok) {
                return;
            }
            unset($this->customer);
        }

        $this->showEdit = false;
        $this->dispatch('notify', type: 'success', message: __('custProfile.medicalUpdated'));
    }

    public function addNote(): void
    {
        $this->validate(['noteText' => ['required', 'string', 'max:500']], [
            'noteText.required' => __('custProfile.noteRequired'),
        ]);

        $note = trim($this->noteText);

        if (! $this->usingFallback()) {
            $ok = $this->waqty(fn () => app(CustomerService::class)->createStaffNote($this->uuid, ['note' => $note]), __('waqty.genericError'));
            if (! $ok) {
                return;
            }
            unset($this->staffNotes);
        } else {
            array_unshift($this->addedNotes, [
                'uuid' => 'local-'.count($this->addedNotes),
                'note' => $note,
                'employee' => ['name' => app(CurrentProvider::class)->name() ?? 'أنت'],
                'created_at' => now()->toDateTimeString(),
            ]);
        }

        $this->noteText = '';
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function render()
    {
        return view('livewire.customers.detail');
    }

    /** @return array<string, mixed> */
    private function fallbackCustomer(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => 'ليلى حسن',
            'email' => 'layla@example.com',
            'phone' => '01012345678',
            'vip' => true,
            'group' => ['name' => 'VIP', 'color' => '#f59e0b'],
            'total_visits' => 24,
            'total_spent' => 1850000,
            'last_visit' => '2026-06-28',
            'allergies' => 'البنسلين، لاتكس',
            'medical_conditions' => 'فروة رأس حساسة',
            'medications' => 'لا يوجد',
            'notes' => 'يفضّل المواعيد المسائية. يطلب الشاي بدون سكر.',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackStatements(): array
    {
        return [
            ['uuid' => 'ST1', 'type' => 'debit', 'amount' => 45000, 'balance' => 45000, 'description' => 'خدمة صبغة شعر', 'created_at' => '2026-06-28 15:20:00'],
            ['uuid' => 'ST2', 'type' => 'credit', 'amount' => 45000, 'balance' => 0, 'description' => 'دفع — بطاقة', 'created_at' => '2026-06-28 16:05:00'],
            ['uuid' => 'ST3', 'type' => 'debit', 'amount' => 20000, 'balance' => 20000, 'description' => 'مانيكير', 'created_at' => '2026-06-15 11:00:00'],
            ['uuid' => 'ST4', 'type' => 'credit', 'amount' => 15000, 'balance' => 5000, 'description' => 'دفعة جزئية — نقدًا', 'created_at' => '2026-06-15 11:30:00'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackReviews(): array
    {
        return [
            ['uuid' => 'RV1', 'rating' => 5, 'comment' => 'خدمة رائعة، سارة هي الأفضل!', 'service' => ['name' => 'صبغة شعر'], 'employee' => ['name' => 'د. سارة أحمد'], 'status' => 'published', 'direction' => 'by_customer', 'created_at' => '2026-06-28 17:00:00'],
            ['uuid' => 'RV2', 'rating' => 4, 'comment' => 'رائعة كالعادة، لكن الانتظار كان طويلاً بعض الشيء.', 'service' => ['name' => 'مانيكير'], 'employee' => ['name' => 'ياسمين فاروق'], 'status' => 'published', 'direction' => 'by_customer', 'created_at' => '2026-06-15 12:00:00'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackNotes(): array
    {
        return [
            ['uuid' => 'N1', 'note' => 'لديها حساسية من البنسلين — تم التنويه عند الاستقبال.', 'employee' => ['name' => 'منى عادل'], 'created_at' => '2026-05-02 09:12:00'],
            ['uuid' => 'N2', 'note' => 'تفضّل سارة لخدمات الصبغة.', 'employee' => ['name' => 'الاستقبال'], 'created_at' => '2026-06-10 14:30:00'],
        ];
    }
}
