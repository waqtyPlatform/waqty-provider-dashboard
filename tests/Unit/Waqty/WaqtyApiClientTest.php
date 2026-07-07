<?php

declare(strict_types=1);

use App\Services\Waqty\WaqtyApiClient;
use App\Services\Waqty\WaqtyApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function client(string $surface = 'provider'): WaqtyApiClient
{
    $c = new WaqtyApiClient('https://api.test', 15);

    return $surface === 'employee' ? $c->asEmployee() : $c;
}

beforeEach(function () {
    config()->set('waqty.get_cache_ttl', 5);
});

it('unwraps the data envelope on success', function () {
    Http::fake([
        'api.test/*' => Http::response(['success' => true, 'message' => 'ok', 'data' => ['x' => 1]]),
    ]);

    expect(client()->get('/api/provider/thing'))->toBe(['x' => 1]);
});

it('injects the provider bearer token and Accept-Language header', function () {
    session()->put(config('waqty.session.provider_token'), 'PROVIDER_JWT');
    app()->setLocale('ar');
    Http::fake(['api.test/*' => Http::response(['success' => true, 'data' => []])]);

    client()->get('/api/provider/thing', cache: false);

    Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer PROVIDER_JWT')
        && $req->hasHeader('Accept-Language', 'ar'));
});

it('uses the employee token surface for asEmployee()', function () {
    session()->put(config('waqty.session.employee_token'), 'EMP_JWT');
    Http::fake(['api.test/*' => Http::response(['success' => true, 'data' => []])]);

    client('employee')->get('/api/employee/thing', cache: false);

    Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer EMP_JWT'));
});

it('maps a 422 to a validation exception', function () {
    Http::fake([
        'api.test/*' => Http::response([
            'message' => 'The given data was invalid.',
            'errors' => ['email' => ['The email field is required.']],
        ], 422),
    ]);

    try {
        client()->post('/api/provider/customers', ['name' => 'x']);
        $this->fail('expected WaqtyApiException');
    } catch (WaqtyApiException $e) {
        expect($e->isValidation())->toBeTrue()
            ->and($e->status)->toBe(422)
            ->and($e->validationErrors)->toBe(['email' => ['The email field is required.']]);
    }
});

it('maps a 401 to an unauthorized exception', function () {
    Http::fake(['api.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    try {
        client()->get('/api/provider/me', cache: false);
        $this->fail('expected WaqtyApiException');
    } catch (WaqtyApiException $e) {
        expect($e->isUnauthorized())->toBeTrue()->and($e->getMessage())->toBe('Unauthenticated.');
    }
});

it('maps a connection timeout to status 0', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28: Operation timed out after 15000ms');
    });

    try {
        client()->get('/api/provider/thing', cache: false);
        $this->fail('expected WaqtyApiException');
    } catch (WaqtyApiException $e) {
        expect($e->status)->toBe(0)->and($e->getMessage())->toBe('Request timed out');
    }
});

it('de-dups identical GETs within the cache window and refetches after a write', function () {
    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;

        return Http::response(['success' => true, 'data' => ['n' => $calls]]);
    });

    client()->get('/api/provider/list');
    client()->get('/api/provider/list'); // served from cache
    expect($calls)->toBe(1);

    client()->post('/api/provider/list', ['a' => 1]); // bumps generation
    client()->get('/api/provider/list'); // cache invalidated -> refetch
    expect($calls)->toBe(3);
});

it('drops null/empty query params and coerces true to "1"', function () {
    Http::fake(['api.test/*' => Http::response(['success' => true, 'data' => []])]);

    client()->get('/api/provider/list', ['status' => 'confirmed', 'branch_uuid' => null, 'q' => '', 'vip' => true], cache: false);

    Http::assertSent(function ($req) {
        $url = $req->url();

        return str_contains($url, 'status=confirmed')
            && str_contains($url, 'vip=1')
            && ! str_contains($url, 'branch_uuid')
            && ! str_contains($url, 'q=');
    });
});
