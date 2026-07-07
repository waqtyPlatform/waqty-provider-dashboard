<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Typed error thrown by {@see WaqtyApiClient}. Mirrors the source `ApiError`
 * (src/lib/api.ts): carries the HTTP status, the API `message`, the raw payload,
 * and (on 422) a field => messages[] validation map.
 */
class WaqtyApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, array<int, string>>  $validationErrors
     */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?array $payload = null,
        public readonly array $validationErrors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $json = null;

        try {
            $json = $response->json();
        } catch (Throwable) {
            $json = null;
        }

        $message = is_array($json) && isset($json['message']) && is_string($json['message'])
            ? $json['message']
            : 'Request failed';

        $validationErrors = [];
        if ($status === 422 && is_array($json) && isset($json['errors']) && is_array($json['errors'])) {
            /** @var array<string, array<int, string>> $validationErrors */
            $validationErrors = $json['errors'];
        }

        return new self($status, $message, is_array($json) ? $json : null, $validationErrors);
    }

    public static function timedOut(): self
    {
        return new self(0, 'Request timed out');
    }

    public static function networkError(?Throwable $previous = null): self
    {
        return new self(0, 'Network error', null, [], $previous);
    }

    public function isValidation(): bool
    {
        return $this->status === 422;
    }

    public function isUnauthorized(): bool
    {
        return $this->status === 401;
    }
}
