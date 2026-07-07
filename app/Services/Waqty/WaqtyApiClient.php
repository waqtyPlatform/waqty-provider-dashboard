<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side port of the source `ApiClient` (src/lib/api.ts).
 *
 * The dashboard owns no domain database: every screen fetches through this
 * client, which unwraps the `{success, message, data}` envelope, injects the
 * bearer token from the session (per provider/employee "surface"), maps 422 to
 * a typed {@see WaqtyApiException}, enforces the 15s timeout, and applies a
 * short-lived GET de-dup cache invalidated on any write.
 */
class WaqtyApiClient
{
    private const CACHE_GEN_KEY = 'waqty:cache_gen';

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 15,
        private readonly string $surface = 'provider',
    ) {}

    /** Return a clone bound to the employee-portal token surface. */
    public function asEmployee(): static
    {
        return new static($this->baseUrl, $this->timeout, 'employee');
    }

    public function get(string $endpoint, array $query = [], bool $cache = true): mixed
    {
        $query = $this->normalizeQuery($query);
        $ttl = (int) config('waqty.get_cache_ttl', 5);

        if (! $cache || $ttl <= 0) {
            return $this->send('get', $endpoint, query: $query);
        }

        return Cache::remember(
            $this->cacheKey($endpoint, $query),
            $ttl,
            fn () => $this->send('get', $endpoint, query: $query),
        );
    }

    public function post(string $endpoint, array $body = []): mixed
    {
        return $this->mutate('post', $endpoint, $body);
    }

    public function put(string $endpoint, array $body = []): mixed
    {
        return $this->mutate('put', $endpoint, $body);
    }

    public function patch(string $endpoint, array $body = []): mixed
    {
        return $this->mutate('patch', $endpoint, $body);
    }

    public function delete(string $endpoint, array $body = []): mixed
    {
        return $this->mutate('delete', $endpoint, $body);
    }

    /**
     * Multipart upload (source `postFormData`). Used by service create/update,
     * profile logo, and bug-report screenshots.
     *
     * @param  array<string, scalar|null>  $fields
     * @param  array<string, UploadedFile>  $files
     */
    public function postFormData(string $endpoint, array $fields = [], array $files = []): mixed
    {
        try {
            $request = $this->pending();

            foreach ($files as $name => $file) {
                $request = $request->attach(
                    $name,
                    $file->get(),
                    $file->getClientOriginalName(),
                );
            }

            $response = $request->asMultipart()->post($endpoint, $this->normalizeQuery($fields));
        } catch (ConnectionException $e) {
            throw $this->connectionException($e);
        }

        if ($response->failed()) {
            throw WaqtyApiException::fromResponse($response);
        }

        $this->bumpGeneration();

        return $response->json('data');
    }

    private function mutate(string $method, string $endpoint, array $body): mixed
    {
        $result = $this->send($method, $endpoint, body: $body);
        $this->bumpGeneration();

        return $result;
    }

    private function send(string $method, string $endpoint, array $query = [], array $body = []): mixed
    {
        try {
            $request = $this->pending();

            $response = match ($method) {
                'get' => $request->get($endpoint, $query),
                'delete' => $request->delete($endpoint, $body),
                default => $request->{$method}($endpoint, $body),
            };
        } catch (ConnectionException $e) {
            throw $this->connectionException($e);
        }

        if ($response->failed()) {
            throw WaqtyApiException::fromResponse($response);
        }

        return $response->json('data');
    }

    private function pending(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['Accept-Language' => app()->getLocale()]);

        if ($token = $this->token()) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function token(): ?string
    {
        $key = config("waqty.session.{$this->surface}_token");

        return $key ? session($key) : null;
    }

    /**
     * Mirror the source `buildQueryString`: drop null/'' entries, coerce true to '1'.
     */
    private function normalizeQuery(array $params): array
    {
        $out = [];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = $value === true ? '1' : ($value === false ? 'false' : $value);
        }

        return $out;
    }

    private function cacheKey(string $endpoint, array $query): string
    {
        return implode(':', [
            'waqty:get',
            $this->surface,
            app()->getLocale(),
            $this->generation(),
            sha1($endpoint.'|'.json_encode($query)),
            sha1((string) $this->token()),
        ]);
    }

    private function generation(): int
    {
        return (int) Cache::get(self::CACHE_GEN_KEY, 0);
    }

    /** Invalidate all cached GETs by advancing the generation stamp (source invalidateGetCache). */
    private function bumpGeneration(): void
    {
        Cache::forever(self::CACHE_GEN_KEY, $this->generation() + 1);
    }

    private function connectionException(ConnectionException $e): WaqtyApiException
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'timeout')
            ? WaqtyApiException::timedOut()
            : WaqtyApiException::networkError($e);
    }
}
