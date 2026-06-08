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

use App\com_pinoox_installer\Request\SetupRequest;
use Pinoox\Component\Http\ApiFormRequest;
use Pinoox\Component\Http\FormRequest;
use Pinoox\Component\Http\ResponseException;
use Pinoox\Component\Kernel\Resolver\FormRequestValueResolver;
use Pinoox\Portal\Validation;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

it('resolves setup request through the form request value resolver', function () {
    $request = appRequest('POST', '/setup', json: [
        'user' => [
            'fname' => 'Ada',
            'lname' => 'Lovelace',
            'email' => 'ada@example.com',
            'username' => 'ada',
            'password' => 'secret123',
        ],
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
        ],
    ]);
    $request->setValidation(Validation::___());

    $resolver = new FormRequestValueResolver();
    $argument = new ArgumentMetadata('request', SetupRequest::class, false, false, null);

    expect($resolver->supports($request, $argument))->toBeTrue();

    $resolved = iterator_to_array($resolver->resolve($request, $argument));

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0])->toBeInstanceOf(SetupRequest::class)
        ->and($resolved[0]->validated('user.email'))->toBe('ada@example.com');
});

it('keeps database password and prefix in validated setup payload', function () {
    $request = appRequest('POST', '/setup', json: [
        'user' => [
            'fname' => 'Ada',
            'lname' => 'Lovelace',
            'email' => 'ada@example.com',
            'username' => 'ada',
            'password' => 'secret123',
        ],
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'root',
            'prefix' => 'pinx_',
        ],
    ]);
    $request->setValidation(Validation::___());

    $formRequest = new SetupRequest($request);
    $formRequest->__resolve();

    expect($formRequest->validated('db.password'))->toBe('root')
        ->and($formRequest->validated('db.prefix'))->toBe('pinx_');
});

it('returns validation failures from api form request in the standard envelope', function () {
    $request = appRequest('POST', '/setup', json: [
        'user' => ['fname' => 'a'],
    ]);
    $request->setValidation(Validation::___());

    $formRequest = new SetupRequest($request);

    try {
        $formRequest->__resolve();
        expect(false)->toBeTrue('Expected ResponseException');
    } catch (ResponseException $exception) {
        $payload = json_decode($exception->getResponse()->getContent(), true);

        expect($exception->getResponse()->getStatusCode())->toBe(422)
            ->and($payload['success'])->toBeFalse()
            ->and($payload['error']['code'])->toBe('VALIDATION_FAILED')
            ->and($payload['error']['message'])->not->toBeEmpty();
    }
});

it('delegates payload helpers from form request to the global request', function () {
    $request = appRequest('POST', '/settings', json: ['mode' => 'manual', 'path' => '/data']);
    $request->setValidation(Validation::___());
    $formRequest = new SetupRequest($request);

    expect($formRequest->payload('mode'))->toBe('manual')
        ->and($formRequest->payloadMany('mode,path'))->toMatchArray([
            'mode' => 'manual',
            'path' => '/data',
        ]);
});

it('extends api form request for app level request classes', function () {
    expect(is_subclass_of(SetupRequest::class, ApiFormRequest::class))->toBeTrue()
        ->and(is_subclass_of(SetupRequest::class, FormRequest::class))->toBeTrue();
});

