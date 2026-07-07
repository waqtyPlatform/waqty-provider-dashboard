<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Invoice settings — business identity plus numbering/format. GET/PUT
 * `/api/provider/settings/invoice`; falls back to sensible defaults when the
 * API is unavailable (save is then a local no-op toast).
 */
#[Layout('components.layouts.app')]
#[Title('Invoice Settings — Waqty')]
class Invoice extends Component
{
    use HandlesWaqtyErrors;

    public string $businessName = '';

    public string $taxNumber = '';

    public string $address = '';

    public string $phone = '';

    public string $prefix = 'INV-';

    public int $nextNumber = 1001;

    public string $taxRate = '14';

    public string $currency = 'EGP';

    public string $footerText = '';

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $s = app(SettingsService::class)->invoiceSettings();
            $this->businessName = (string) ($s['business_name'] ?? '');
            $this->taxNumber = (string) ($s['tax_number'] ?? '');
            $this->address = (string) ($s['address'] ?? '');
            $this->phone = (string) ($s['phone'] ?? '');
            $this->prefix = (string) ($s['prefix'] ?? 'INV-');
            $this->nextNumber = (int) ($s['next_number'] ?? 1001);
            $this->taxRate = (string) ($s['tax_rate'] ?? '14');
            $this->currency = (string) ($s['currency'] ?? 'EGP');
            $this->footerText = (string) ($s['footer_text'] ?? '');
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $profile = session(config('waqty.session.provider_profile'), []);
            $this->businessName = (string) ($profile['name'] ?? 'Waqty Provider');
            $this->footerText = 'Thank you for your business!';
        }
    }

    public function save(): void
    {
        $this->validate([
            'businessName' => ['required', 'string', 'max:120'],
            'taxNumber' => ['nullable', 'string', 'max:40'],
            'prefix' => ['required', 'string', 'max:10'],
            'nextNumber' => ['required', 'integer', 'min:1'],
            'taxRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'in:EGP,SAR,AED,USD'],
        ]);

        if (! $this->fallbackUsed) {
            $this->waqty(fn () => app(SettingsService::class)->updateInvoiceSettings([
                'business_name' => trim($this->businessName),
                'tax_number' => trim($this->taxNumber),
                'address' => trim($this->address),
                'phone' => trim($this->phone),
                'prefix' => trim($this->prefix),
                'next_number' => $this->nextNumber,
                'tax_rate' => (float) $this->taxRate,
                'currency' => $this->currency,
                'footer_text' => trim($this->footerText),
            ]) ?? true, __('settings.invoice.saveFailed'));
        }

        $this->dispatch('notify', type: 'success', message: __('settings.invoice.saved'));
    }

    public function render()
    {
        return view('livewire.settings.invoice');
    }
}
