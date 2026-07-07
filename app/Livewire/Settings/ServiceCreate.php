<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Settings › Services › New — a 6-step service creation wizard.
 *
 * Steps: 1 basics, 2 pricing, 3 duration, 4 resourcing, 5 commission,
 * 6 review + media + submit. Each step validates its own fields before
 * advancing (mirrors OnboardingWizard). Submit posts multipart FormData via
 * ServiceCatalogService::createService; because this is a UI clone with no
 * guaranteed backend, an API failure is swallowed so the flow still confirms
 * success and returns to the catalog (demo parity with OnboardingWizard::finish).
 */
#[Layout('components.layouts.app')]
#[Title('Add New Service — Waqty')]
class ServiceCreate extends Component
{
    use WithFileUploads;

    private const LAST_STEP = 6;

    public int $step = 1;

    // Step 1 — basics
    public string $name = '';

    public string $description = '';

    public string $category = '';

    // Step 2 — pricing
    public string $price = '';

    public string $tierNote = '';

    // Step 3 — duration
    public int $duration = 30;

    // Step 4 — resourcing (local only)
    public string $resource = 'none';

    public int $capacity = 1;

    // Step 5 — commission (local only)
    public string $commission = '';

    // Step 6 — media
    public $image = null;

    public function next(): void
    {
        $this->validateStep();

        if ($this->step < self::LAST_STEP) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    /** Jump back to an already-completed step from the progress bar. */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->messages());

        $fields = array_filter([
            'name' => trim($this->name),
            'category' => trim($this->category) ?: null,
            'description' => trim($this->description) ?: null,
            'estimated_duration_minutes' => $this->duration,
            'base_price' => $this->price !== '' ? Money::toMinor((float) $this->price) : null,
            'commission_percent' => $this->commission !== '' ? (float) $this->commission : null,
            'required_resource' => $this->resource !== 'none' ? $this->resource : null,
            'capacity' => $this->capacity,
            'active' => '1',
        ], fn ($v) => $v !== null);

        $files = $this->image ? ['image' => $this->image] : [];

        try {
            app(ServiceCatalogService::class)->createService($fields, $files);
        } catch (WaqtyApiException) {
            // UI clone — no real backend required; proceed as a successful create.
        }

        $this->dispatch('notify', type: 'success', message: __('settings.services.new.created'));
        $this->redirectRoute('settings.services', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.service-create');
    }

    /** Validate only the fields belonging to the current step before advancing. */
    private function validateStep(): void
    {
        $rules = match ($this->step) {
            1 => ['name' => $this->rules()['name'], 'description' => $this->rules()['description'], 'category' => $this->rules()['category']],
            2 => ['price' => $this->rules()['price'], 'tierNote' => $this->rules()['tierNote']],
            3 => ['duration' => $this->rules()['duration']],
            4 => ['resource' => $this->rules()['resource'], 'capacity' => $this->rules()['capacity']],
            5 => ['commission' => $this->rules()['commission']],
            default => [],
        };

        if ($rules !== []) {
            $this->validate($rules, $this->messages());
        }
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tierNote' => ['nullable', 'string', 'max:200'],
            'duration' => ['required', 'integer', 'min:5', 'max:480'],
            'resource' => ['required', 'in:none,chair,room,equipment'],
            'capacity' => ['required', 'integer', 'min:1', 'max:99'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => __('settings.services.new.nameRequired'),
        ];
    }
}
