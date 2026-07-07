<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing hub landing page. The source `marketingApi` is mock-only, so the
 * hub surfaces Arabic sample previews for each channel (offers, promo codes,
 * message templates, campaigns) with links out to the dedicated screens.
 */
#[Layout('components.layouts.app')]
#[Title('Marketing — Waqty')]
class Hub extends Component
{
    /** offers | promos | messages | campaigns */
    public string $tab = 'offers';

    /** @var array<int, array<string, mixed>> */
    public array $offers = [];

    /** @var array<int, array<string, mixed>> */
    public array $promos = [];

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    /** @var array<int, array<string, mixed>> */
    public array $campaigns = [];

    public function mount(): void
    {
        $this->offers = [
            ['id' => 1, 'name' => 'انطلاقة الصيف', 'type' => 'percentage', 'value' => 20, 'active' => true],
            ['id' => 2, 'name' => 'ترحيب العملاء الجدد', 'type' => 'fixed', 'value' => 5000, 'active' => true],
            ['id' => 3, 'name' => 'مكافأة الولاء', 'type' => 'percentage', 'value' => 15, 'active' => true],
        ];

        $this->promos = [
            ['id' => 1, 'code' => 'SUMMER20', 'type' => 'percentage', 'value' => 20, 'used' => 84],
            ['id' => 2, 'code' => 'WELCOME50', 'type' => 'fixed', 'value' => 5000, 'used' => 143],
            ['id' => 3, 'code' => 'VIP15', 'type' => 'percentage', 'value' => 15, 'used' => 37],
        ];

        $this->messages = [
            ['id' => 'msg-1', 'name' => 'تأكيد الحجز', 'channel' => 'sms'],
            ['id' => 'msg-2', 'name' => 'طلب تقييم', 'channel' => 'whatsapp'],
            ['id' => 'msg-3', 'name' => 'عرض عيد الميلاد', 'channel' => 'email'],
        ];

        $this->campaigns = [
            ['id' => 'camp-1', 'name' => 'حملة تخفيضات الصيف', 'channel' => 'sms', 'status' => 'active'],
            ['id' => 'camp-2', 'name' => 'معاينة كبار العملاء', 'channel' => 'email', 'status' => 'draft'],
            ['id' => 'camp-3', 'name' => 'استعادة العملاء - الربع الأول', 'channel' => 'whatsapp', 'status' => 'ended'],
        ];
    }

    /**
     * The hub renders local sample previews only (marketing API is mock-only in
     * the source), so it always presents demo data.
     */
    public function usingFallback(): bool
    {
        return true;
    }

    /** @return array{activeOffers:int, redemptions:int, messagesSent:int, reach:int} */
    #[Computed]
    public function kpis(): array
    {
        return [
            'activeOffers' => count(array_filter($this->offers, fn ($o) => $o['active'])),
            'redemptions' => array_sum(array_map(fn ($p) => (int) $p['used'], $this->promos)),
            'messagesSent' => 1240,
            'reach' => 8600,
        ];
    }

    /** Top few items of the active tab. @return array<int, array<string, mixed>> */
    #[Computed]
    public function preview(): array
    {
        return array_slice(match ($this->tab) {
            'promos' => $this->promos,
            'messages' => $this->messages,
            'campaigns' => $this->campaigns,
            default => $this->offers,
        }, 0, 3);
    }

    /** Named route of the dedicated page for the active tab. */
    public function viewAllRoute(): string
    {
        return match ($this->tab) {
            'promos' => 'marketing.promo-codes',
            'messages' => 'marketing.messages',
            'campaigns' => 'marketing.campaigns',
            default => 'marketing.offers',
        };
    }

    public function render()
    {
        return view('livewire.marketing.hub');
    }
}
