<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\ProviderRatingData;
use App\Services\Waqty\ReviewService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Review Moderation — Waqty')]
class Reviews extends Component
{
    use HandlesWaqtyErrors;

    public string $statusFilter = 'all';

    public string $ratingFilter = 'all';

    // Report modal
    public bool $showReport = false;

    public ?string $reportUuid = null;

    public string $reportCategory = 'inappropriate';

    public string $reportReason = '';

    /** Optimistic status after a moderation action. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, ProviderRatingData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ProviderRatingData> Fetched unfiltered so KPIs stay global. */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ReviewService::class)->ratings(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ProviderRatingData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $r) {
            if (isset($this->overrides[$r->uuid])) {
                $r->status = $this->overrides[$r->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ProviderRatingData> */
    #[Computed]
    public function filtered(): array
    {
        $status = $this->statusFilter;
        $rating = $this->ratingFilter;

        return array_values(array_filter($this->source(), function (ProviderRatingData $r) use ($status, $rating) {
            $matchesStatus = $status === 'all' || $r->status === $status;
            $matchesRating = $rating === 'all' || $r->rating === (int) $rating;

            return $matchesStatus && $matchesRating;
        }));
    }

    /** @return array{total:int, avg:float, published:int, pending:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $ratings = array_map(fn (ProviderRatingData $r) => $r->rating, $all);

        return [
            'total' => count($all),
            'avg' => $all === [] ? 0.0 : round(array_sum($ratings) / count($all), 1),
            'published' => count(array_filter($all, fn (ProviderRatingData $r) => $r->status === 'published')),
            'pending' => count(array_filter($all, fn (ProviderRatingData $r) => $r->status === 'pending')),
        ];
    }

    public function openReport(string $uuid): void
    {
        $this->reportUuid = $uuid;
        $this->reportCategory = 'inappropriate';
        $this->reportReason = '';
        $this->resetValidation();
        $this->showReport = true;
    }

    public function submitReport(): void
    {
        $this->validate(['reportReason' => ['required', 'string', 'max:500']], [
            'reportReason.required' => __('reviews.reportReason'),
        ]);

        $uuid = $this->reportUuid;
        $reason = trim($this->reportCategory.': '.$this->reportReason);

        if (! $this->usingFallback()) {
            $ok = $this->waqty(fn () => app(ReviewService::class)->flag($uuid, $reason) ?? true, __('waqty.genericError'));
            if (! $ok) {
                return;
            }
        }

        $this->overrides[$uuid] = 'reported';
        $this->showReport = false;
        $this->reportUuid = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('custProfile.reviewReportedMsg'));
    }

    public function render()
    {
        return view('livewire.customers.reviews');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'R1', 'rating' => 5, 'comment' => 'خدمة رائعة، سارة هي الأفضل! أنصح بها بشدة.', 'status' => 'published', 'user' => ['name' => 'ليلى حسن'], 'booking' => ['booking_date' => '2026-06-28'], 'created_at' => '2026-06-28 17:00:00'],
            ['uuid' => 'R2', 'rating' => 4, 'comment' => 'رائعة كالعادة، لكن الانتظار كان طويلاً بعض الشيء.', 'status' => 'published', 'user' => ['name' => 'عمر خالد'], 'booking' => ['booking_date' => '2026-06-15'], 'created_at' => '2026-06-15 12:00:00'],
            ['uuid' => 'R3', 'rating' => 2, 'comment' => 'لست راضية عن النتيجة، اضطررت للعودة مرة أخرى.', 'status' => 'pending', 'user' => ['name' => 'يوسف علي'], 'booking' => ['booking_date' => '2026-06-10'], 'created_at' => '2026-06-10 19:30:00'],
            ['uuid' => 'R4', 'rating' => 5, 'comment' => 'تجربة مثالية من البداية إلى النهاية.', 'status' => 'published', 'user' => ['name' => 'مريم عادل'], 'booking' => ['booking_date' => '2026-07-01'], 'created_at' => '2026-07-01 14:20:00'],
            ['uuid' => 'R5', 'rating' => 1, 'comment' => 'محتوى ترويجي أشبه بالرسائل المزعجة.', 'status' => 'reported', 'user' => ['name' => 'غير معروف'], 'booking' => ['booking_date' => '2026-05-30'], 'created_at' => '2026-05-30 09:00:00'],
            ['uuid' => 'R6', 'rating' => 3, 'comment' => 'كانت لا بأس بها، لا شيء مميز.', 'status' => 'pending', 'user' => ['name' => 'سلمى إبراهيم'], 'booking' => ['booking_date' => '2026-06-22'], 'created_at' => '2026-06-22 16:45:00'],
        ];
    }
}
