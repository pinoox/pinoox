<?php

use Pinoox\PinDoc\DocsAppUrlResolver;

it('builds api base url from explicit app url', function () {
    $urls = DocsAppUrlResolver::resolve(
        'com_pinoox_installer',
        ['url' => 'https://example.com/pinoox'],
        '/api/v1',
    );

    expect($urls['app_url'])->toBe('https://example.com/pinoox')
        ->and($urls['app_url_explicit'])->toBeTrue()
        ->and($urls['api_base_url'])->toBe('https://example.com/pinoox/api/v1');
});

it('leaves app url empty when docs url is not explicit', function () {
    $urls = DocsAppUrlResolver::resolve(
        'com_pinoox_installer',
        [],
        '/api/v1',
    );

    expect($urls['app_url'])->toBe('')
        ->and($urls['app_url_explicit'])->toBeFalse();
});

it('builds operation url from document api base', function () {
    $document = [
        'api_base_url' => 'https://example.com/pinoox/api/v1',
        'site_url' => 'https://example.com',
    ];

    $url = DocsAppUrlResolver::operationUrl($document, ['path' => '/api/v1/ping']);

    expect($url)->toBe('https://example.com/api/v1/ping');
});

