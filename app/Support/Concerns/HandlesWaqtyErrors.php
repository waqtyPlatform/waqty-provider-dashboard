<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Services\Waqty\WaqtyApiException;

/**
 * Livewire trait that runs a Waqty API call and translates a
 * {@see WaqtyApiException} into UX: 422 -> error bag, 401 -> re-login,
 * anything else -> an error toast. Returns null on failure.
 *
 * Usage:  $customer = $this->waqty(fn () => $this->customers->create($data), 'Could not save client');
 */
trait HandlesWaqtyErrors
{
    protected function waqty(callable $callback, ?string $errorMessage = null): mixed
    {
        try {
            return $callback();
        } catch (WaqtyApiException $e) {
            if ($e->isUnauthorized()) {
                session()->forget([
                    config('waqty.session.provider_token'),
                    config('waqty.session.provider_profile'),
                ]);
                $this->dispatch('notify', type: 'error', message: __('waqty.sessionExpired'));
                $this->redirect(route('login'), navigate: true);

                return null;
            }

            if ($e->isValidation()) {
                foreach ($e->validationErrors as $field => $messages) {
                    $this->addError($field, is_array($messages) ? ($messages[0] ?? '') : (string) $messages);
                }

                return null;
            }

            $this->dispatch('notify', type: 'error', message: $errorMessage ?? $e->getMessage());

            return null;
        }
    }
}
