<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

use App\com_pinoox_installer\Component\InstallerDatabase;
use App\com_pinoox_manager\Controller\Api;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\ResponseException;
use Pinoox\Component\Validation\ValidationException;
use Pinoox\Portal\Validation;

function requestWithValidation(Request $request): Request
{
    $request->setValidation(Validation::___());

    return $request;
}

it('reads json body through payload and getPayload', function () {
    $request = appRequest('POST', '/api/test', json: [
        'mode' => 'auto',
        'limit_gb' => 2,
    ]);

    expect($request->getPayload()->get('mode'))->toBe('auto')
        ->and($request->payload('limit_gb'))->toBe(2)
        ->and($request->isJson())->toBeTrue();
});

it('reads post fields through payload on non-json requests', function () {
    $request = appRequest('POST', '/save', data: [
        'title' => 'Coming soon',
        'twitter' => '@pinoox',
    ]);

    expect($request->payload('title'))->toBe('Coming soon')
        ->and($request->payloadMany('title,twitter'))->toMatchArray([
            'title' => 'Coming soon',
            'twitter' => '@pinoox',
        ]);
});

it('reads query string as payload on get requests', function () {
    $request = appRequest('GET', '/browse', query: ['path' => '/uploads']);

    expect($request->payload('path'))->toBe('/uploads')
        ->and($request->queryOne('path'))->toBe('/uploads');
});

it('extracts nested keys with dot notation', function () {
    $request = appRequest('POST', '/setup', json: [
        'user' => ['email' => 'admin@example.com'],
    ]);

    expect($request->payload('user.email'))->toBe('admin@example.com')
        ->and($request->get('user.email'))->toBe('admin@example.com');
});

it('filters payload keys with only and except', function () {
    $request = appRequest('POST', '/widgets', json: [
        'widgets' => ['clock'],
        'ignored' => true,
    ]);

    expect($request->only(['widgets']))->toBe(['widgets' => ['clock']])
        ->and($request->except(['ignored']))->toBe(['widgets' => ['clock']]);
});

it('reads requestOne from post bag instead of json bag', function () {
    $request = appRequest('POST', '/form', data: ['username' => 'root']);

    expect($request->requestOne('username'))->toBe('root')
        ->and($request->jsonOne('username'))->toBeNull();
});

it('reads database credentials from payloadMany in installer helper', function () {
    $request = appRequest('POST', '/check-db', json: [
        'host' => '127.0.0.1',
        'database' => 'pinoox',
        'username' => 'root',
        'password' => 'secret',
        'prefix' => 'pin_',
    ]);

    expect(InstallerDatabase::readFromRequest($request))->toMatchArray([
        'host' => '127.0.0.1',
        'database' => 'pinoox',
        'username' => 'root',
        'password' => 'secret',
        'prefix' => 'pin_',
    ]);
});

it('validates request input and throws validation exception', function () {
    $request = requestWithValidation(appRequest('POST', '/login', json: []));

    expect(fn () => $request->validate(['username' => 'required']))
        ->toThrow(ValidationException::class);
});

it('returns validated data from request validate helper', function () {
    $request = requestWithValidation(appRequest('POST', '/login', json: [
        'username' => 'admin',
        'password' => 'secret',
    ]));

    expect($request->validate([
        'username' => 'required',
        'password' => 'required',
    ]))->toMatchArray([
        'username' => 'admin',
        'password' => 'secret',
    ]);
});

it('maps manager validated helper failures to the standard error envelope', function () {
    $controller = new class extends Api {
        public function probe(Request $request): never
        {
            $this->validated($request, ['email' => 'required|email']);
        }
    };

    $request = requestWithValidation(appRequest('POST', '/profile', json: ['email' => 'invalid']));

    try {
        $controller->probe($request);
        expect(false)->toBeTrue('Expected ResponseException');
    } catch (ResponseException $exception) {
        $payload = json_decode($exception->getResponse()->getContent(), true);

        expect($payload['success'])->toBeFalse()
            ->and($payload['error']['code'])->toBe('API_ERROR')
            ->and($payload['error']['message'])->not->toBeEmpty();
    }
});

