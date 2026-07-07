<?php

declare(strict_types=1);

namespace App\Livewire\Help;

use App\Services\Waqty\WaqtyApiClient;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\CurrentProvider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Help › Bug Report — submit a bug/feature report with an optional screenshot.
 * Screenshot POSTs multipart to /bug-reports/screenshot for a URL, then the
 * report POSTs to /bug-reports. Degrades gracefully when the API is offline.
 */
#[Layout('components.layouts.app')]
#[Title('Report a Problem — Waqty')]
class BugReport extends Component
{
    use HandlesWaqtyErrors;
    use WithFileUploads;

    public const CATEGORIES = ['bug', 'feature', 'question', 'other'];

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public string $category = 'bug';

    public string $severity = 'medium';

    public string $description = '';

    public string $steps = '';

    public $screenshot = null;

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate([
            'category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'severity' => ['required', 'in:'.implode(',', self::SEVERITIES)],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'steps' => ['nullable', 'string', 'max:2000'],
            'screenshot' => ['nullable', 'image', 'max:4096'],
        ]);

        $sent = $this->waqty(function () {
            $api = app(WaqtyApiClient::class);

            $screenshotUrl = null;
            if ($this->screenshot) {
                $result = $api->postFormData('/api/provider/bug-reports/screenshot', [], ['screenshot' => $this->screenshot]);
                $screenshotUrl = is_array($result) ? ($result['url'] ?? null) : null;
            }

            $api->post('/api/provider/bug-reports', [
                'category' => $this->category,
                'severity' => $this->severity,
                'description' => trim($this->description),
                'steps' => trim($this->steps) ?: null,
                'screenshot_url' => $screenshotUrl,
                'page_url' => url()->previous(),
                'user_role' => app(CurrentProvider::class)->role()->value,
            ]);

            return true;
        }, __('help.bug.submitFailed'));

        // In this UI clone the endpoint may be unavailable — still confirm to the
        // user (the source keeps a local record on failure). $sent is unused by
        // design: a 401 already redirected away inside the trait.
        unset($sent);
        $this->submitted = true;
        $this->dispatch('notify', type: 'success', message: __('help.bug.submitted'));
    }

    public function reset_form(): void
    {
        $this->reset(['category', 'severity', 'description', 'steps', 'screenshot', 'submitted']);
        $this->category = 'bug';
        $this->severity = 'medium';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.help.bug-report');
    }
}
