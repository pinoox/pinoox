<?php

use Pinoox\Component\Template\Frontend\ThemeSsr;

it('detects dynamic context from bootstrap payload', function () {
    $themePath = sys_get_temp_dir() . '/pinoox-ssr-test-' . uniqid();
    mkdir($themePath . '/dist/ssr', 0777, true);
    file_put_contents($themePath . '/dist/ssr/meta.json', json_encode([
        'locale' => 'fa',
        'direction' => 'rtl',
        'url' => ['BASE' => '/', 'MANAGER' => '/manager'],
    ]));

    expect(ThemeSsr::isDynamicContext($themePath, [
        'bootstrap' => ['data' => ['items' => [1, 2]]],
    ]))->toBeTrue();

    expect(ThemeSsr::isDynamicContext($themePath, [
        'bootstrap' => ['locale' => 'en'],
    ]))->toBeTrue();

    expect(ThemeSsr::isDynamicContext($themePath, [
        'bootstrap' => ['locale' => 'fa', 'url' => ['BASE' => '/', 'MANAGER' => '/manager']],
    ]))->toBeFalse();

    expect(ThemeSsr::isDynamicContext($themePath, [
        'ssr' => ['dynamic' => true],
        'bootstrap' => [],
    ]))->toBeTrue();
});

it('returns static fragment when strategy is static', function () {
    $themePath = sys_get_temp_dir() . '/pinoox-ssr-static-' . uniqid();
    mkdir($themePath . '/dist/ssr', 0777, true);
    file_put_contents($themePath . '/dist/ssr/app.html', '<div>static</div>');
    file_put_contents($themePath . '/frontend.config.php', '<?php return [];');

    $config = [
        'ssr' => [
            'enabled' => true,
            'strategy' => ThemeSsr::STRATEGY_STATIC,
            'fragment' => 'dist/ssr/app.html',
        ],
    ];

    $result = ThemeSsr::resolve($themePath, [], $config);

    expect($result->hasHtml())->toBeTrue();
    expect($result->html)->toBe('<div>static</div>');
    expect($result->strategy)->toBe(ThemeSsr::STRATEGY_STATIC);
});

it('falls back to csr when dynamic render is unavailable', function () {
    $themePath = sys_get_temp_dir() . '/pinoox-ssr-fallback-' . uniqid();

    $config = [
        'ssr' => [
            'enabled' => true,
            'strategy' => ThemeSsr::STRATEGY_DYNAMIC,
            'fallback' => ThemeSsr::FALLBACK_CSR,
            'server' => 'dist/server/entry-server.mjs',
        ],
    ];

    $result = ThemeSsr::resolve($themePath, [
        'bootstrap' => ['data' => ['x' => 1]],
    ], $config);

    expect($result->html)->toBeNull();
    expect($result->fallback)->toBe(ThemeSsr::FALLBACK_CSR);
});
