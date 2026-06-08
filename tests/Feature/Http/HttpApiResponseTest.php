<?php

use App\com_pinoox_installer\Resource\PingResource;
use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Http\Api\PayloadResource;

it('returns a standard success envelope', function () {
    $response = ApiResponse::success(['connected' => true], 'connect');
    $payload = json_decode($response->getContent(), true);

    expect($payload)
        ->toMatchArray([
            'success' => true,
            'data' => ['connected' => true],
            'message' => 'connect',
            'meta' => [],
        ]);
});

it('serializes api resources into the data payload', function () {
    $response = ApiResponse::success(new PingResource(['timestamp' => 1710000000]));
    $payload = json_decode($response->getContent(), true);

    expect($payload['success'])->toBeTrue()
        ->and($payload['data'])->toMatchArray([
            'ok' => true,
            'routing' => true,
            'timestamp' => 1710000000,
        ]);
});

it('returns a standard error envelope', function () {
    $response = ApiResponse::error('DB_CONNECTION_FAILED', 'disconnect', [], 422);
    $payload = json_decode($response->getContent(), true);

    expect($payload['success'])->toBeFalse()
        ->and($payload['error']['code'])->toBe('DB_CONNECTION_FAILED')
        ->and($payload['error']['message'])->toBe('disconnect');
});

it('wraps arbitrary arrays with payload resource', function () {
    $resource = new PayloadResource(['items' => ['rewrite' => ['state' => 'pass']]]);

    expect($resource->toArray())->toBe(['items' => ['rewrite' => ['state' => 'pass']]]);
});

it('defaults success message to OK when omitted', function () {
    $response = ApiResponse::success(['id' => 1]);
    $payload = json_decode($response->getContent(), true);

    expect($payload['message'])->toBe('OK');
});

it('serializes resource collections inside array data', function () {
    $response = ApiResponse::success([
        new PingResource(['timestamp' => 1]),
        new PingResource(['timestamp' => 2]),
    ]);
    $payload = json_decode($response->getContent(), true);

    expect($payload['data'])->toHaveCount(2)
        ->and($payload['data'][0]['timestamp'])->toBe(1)
        ->and($payload['data'][1]['timestamp'])->toBe(2);
});

it('stores validation details on error payloads', function () {
    $response = ApiResponse::error(
        'VALIDATION_FAILED',
        'Invalid input',
        ['email' => ['required']],
        422,
        translate: false,
    );
    $payload = json_decode($response->getContent(), true);

    expect($payload['error']['details'])->toBe(['email' => ['required']]);
});

