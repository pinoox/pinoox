<?php

use App\com_pinoox_manager\Controller\Api;
use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Kernel\Controller\ApiController;

it('maps manager message with payload to the standard success envelope', function () {
    $controller = new class extends Api {
    };

    $response = $controller->message(['fname' => 'Ada']);
    $payload = json_decode($response->getContent(), true);

    expect($payload)
        ->toMatchArray([
            'success' => true,
            'data' => ['fname' => 'Ada'],
            'meta' => [],
        ]);
});

it('maps manager message with result false to success data false', function () {
    $controller = new class extends Api {
    };

    $response = $controller->message('manager.invalid_request', false);
    $payload = json_decode($response->getContent(), true);

    expect($payload['success'])->toBeTrue()
        ->and($payload['data'])->toBeFalse();
});

it('maps manager message with secondary result to data field', function () {
    $controller = new class extends Api {
    };

    $response = $controller->message('user.logged_in_successfully', 'token-123');
    $payload = json_decode($response->getContent(), true);

    expect($payload['success'])->toBeTrue()
        ->and($payload['data'])->toBe('token-123');
});

it('maps manager error to the standard error envelope', function () {
    $controller = new class extends Api {
    };

    $response = $controller->error('manager.request_not_valid', 422);
    $payload = json_decode($response->getContent(), true);

    expect($payload['success'])->toBeFalse()
        ->and($payload['error']['code'])->toBe('API_ERROR')
        ->and($payload['error']['details'])->toBe([]);
});

it('exposes ok and fail helpers on the base api controller', function () {
    $controller = new class extends ApiController {
        public function probe(): \Pinoox\Component\Http\JsonResponse
        {
            return $this->ok(['ready' => true], 'OK', translate: false);
        }

        public function deny(): \Pinoox\Component\Http\JsonResponse
        {
            return $this->fail('ACCESS_DENIED', 'Access denied!', status: 401, translate: false);
        }
    };

    $ok = json_decode($controller->probe()->getContent(), true);
    $fail = json_decode($controller->deny()->getContent(), true);

    expect($ok['success'])->toBeTrue()
        ->and($ok['data'])->toBe(['ready' => true])
        ->and($fail['success'])->toBeFalse()
        ->and($fail['error']['code'])->toBe('ACCESS_DENIED');
});

it('returns auth flow errors in the standard envelope', function () {
    $response = ApiResponse::error('ACCESS_DENIED', 'Access denied!', status: 401, translate: false);
    $payload = json_decode($response->getContent(), true);

    expect($payload)->toMatchArray([
        'success' => false,
        'error' => [
            'code' => 'ACCESS_DENIED',
            'message' => 'Access denied!',
            'details' => [],
        ],
    ]);
});

