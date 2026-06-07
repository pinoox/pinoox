<?php

use Pinoox\PinDoc\Api\Docs\ControllerDocInferrer;
use App\com_pinoox_installer\Controller\ApiController;

it('infers response payload from controller return arrays', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'GET',
        'uri' => '/ping',
        'full_uri' => '/api/v1/ping',
        'action' => [ApiController::class, 'ping'],
        'name' => 'ping',
        'flow' => [],
    ]);

    expect($route['responses'][0]['example'] ?? null)->toMatchArray([
        'ok' => true,
        'routing' => true,
    ]);
});

it('infers request body from controller validation rules', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'POST',
        'uri' => '/setup',
        'full_uri' => '/api/v1/setup',
        'action' => [ApiController::class, 'setup'],
        'name' => 'setup',
        'flow' => [],
    ]);

    expect($route['body']['user']['fname'] ?? null)->toBe('string')
        ->and($route['body_example']['user']['fname'] ?? null)->toBe('John')
        ->and($route['body_example']['user']['email'] ?? null)->toBe('admin@example.com')
        ->and($route['body_example']['db']['host'] ?? null)->toBe('127.0.0.1')
        ->and($route['responses'])->not->toBeEmpty();
});

it('infers component response payloads for bootstrap diagnostics', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'GET',
        'uri' => '/bootstrap/diagnostics',
        'full_uri' => '/api/v1/bootstrap/diagnostics',
        'action' => [ApiController::class, 'bootstrapDiagnostics'],
        'name' => 'bootstrap.diagnostics',
        'flow' => [],
    ]);

    expect($route['responses'][0]['example']['steps']['rewrite']['state'] ?? null)->toBe('pass')
        ->and($route['responses'][0]['example'])->toHaveKey('via_query_route');
});

it('infers prerequisites payload from component methods', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'GET',
        'uri' => '/checkPrerequisites',
        'full_uri' => '/api/v1/checkPrerequisites',
        'action' => [ApiController::class, 'checkAllPrerequisites'],
        'name' => 'prerequisites.all',
        'flow' => [],
    ]);

    expect($route['responses'][0]['example']['items'] ?? null)->toBeArray()
        ->and($route['responses'][0]['example'])->toHaveKey('canContinue');
});

it('infers realistic path param examples for prerequisite type checks', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'GET',
        'uri' => '/checkPrerequisites/{type}',
        'full_uri' => '/api/v1/checkPrerequisites/{type}',
        'action' => [ApiController::class, 'checkPrerequisites'],
        'name' => 'prerequisites.type',
        'flow' => [],
        'params' => [],
    ]);

    expect($route['params'][0]['example'] ?? null)->toBe('mod_rewrite')
        ->and($route['params'][0]['description'] ?? '')->toContain('free_space');
});

it('infers database body fields from generateConfig helper', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'POST',
        'uri' => '/checkDB',
        'full_uri' => '/api/v1/checkDB',
        'action' => [ApiController::class, 'checkDB'],
        'name' => 'checkDB',
        'flow' => [],
    ]);

    expect($route['body'])->toHaveKeys(['host', 'database', 'username', 'password', 'prefix']);
});

it('infers path params from controller method signature', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'GET',
        'uri' => '/checkPrerequisites/{type}',
        'full_uri' => '/api/v1/checkPrerequisites/{type}',
        'action' => [ApiController::class, 'checkPrerequisites'],
        'name' => 'prerequisites.type',
        'flow' => [],
        'params' => [],
    ]);

    expect($route['params'][0]['name'] ?? null)->toBe('type')
        ->and($route['params'][0]['in'] ?? null)->toBe('path');
});

it('adds auth and validation error responses automatically', function () {
    $inferrer = new ControllerDocInferrer();

    $route = $inferrer->enrich([
        'method' => 'POST',
        'uri' => '/setup',
        'full_uri' => '/api/v1/setup',
        'action' => [ApiController::class, 'setup'],
        'name' => 'setup',
        'flow' => ['auth:api'],
        'body' => ['user' => ['email' => 'string']],
    ]);

    $statuses = array_column($route['responses'], 'status');

    expect($statuses)->toContain(401)
        ->and($statuses)->toContain(422);
});

it('resolves api docs metadata from app profile when route docs are omitted', function () {
    $profile = [
        'package' => 'com_pinoox_installer',
        'name' => 'installer',
        'title' => 'Installer',
        'description' => 'Pinoox web installer for first-time project setup.',
        'docs' => [
            'audience' => 'external',
            'path' => 'docs/api',
        ],
    ];

    $docs = \Pinoox\PinDoc\AppDocProfile::resolveDocs([], $profile, 'rest');

    expect($docs['title'])->toBe('Installer API')
        ->and($docs['description'])->toBe('Pinoox web installer for first-time project setup.')
        ->and($docs['audience'])->toBe('external')
        ->and($docs['path'])->toBe('docs/api');
});

it('prefers route docs overrides over app profile docs', function () {
    $profile = [
        'title' => 'Installer',
        'description' => 'From app.php',
        'docs' => ['title' => 'App level title'],
    ];

    $docs = \Pinoox\PinDoc\AppDocProfile::resolveDocs(['title' => 'Route level title'], $profile, 'rest');

    expect($docs['title'])->toBe('Route level title')
        ->and($docs['description'])->toBe('From app.php');
});

it('resolves app and api urls from docs config and router path', function () {
    $urls = \Pinoox\PinDoc\DocsAppUrlResolver::resolve(
        'com_pinoox_installer',
        ['url' => 'https://example.com/pinoox'],
        '/api/v1',
    );

    expect($urls['app_url'])->toBe('https://example.com/pinoox')
        ->and($urls['app_url_explicit'])->toBeTrue()
        ->and($urls['api_base_url'])->toBe('https://example.com/pinoox/api/v1');
});

it('does not expose app url when it was not configured explicitly', function () {
    $urls = \Pinoox\PinDoc\DocsAppUrlResolver::resolve(
        'com_pinoox_installer',
        [],
        '/api/v1',
    );

    expect($urls['app_url'])->toBe('')
        ->and($urls['app_url_explicit'])->toBeFalse();
});

it('does not auto-generate placeholder operation descriptions', function () {
    $enricher = new \Pinoox\PinDoc\Api\Docs\RouteDocEnricher();

    $route = $enricher->enrich([
        'method' => 'POST',
        'uri' => '/setup',
        'full_uri' => '/api/v1/setup',
        'name' => 'setup',
    ]);

    expect($route['summary'])->toBe('Setup')
        ->and($route['description'] ?? '')->toBe('');
});

it('builds full operation url for examples', function () {
    $url = \Pinoox\PinDoc\DocsAppUrlResolver::operationUrl(
        [
            'site_url' => 'https://example.com/pinoox',
            'api_base_url' => 'https://example.com/pinoox/api/v1',
        ],
        ['path' => '/api/v1/ping'],
    );

    expect($url)->toBe('https://example.com/pinoox/api/v1/ping');
});

