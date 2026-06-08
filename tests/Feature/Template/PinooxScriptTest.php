<?php

use Pinoox\Portal\App\App;
use Pinoox\Portal\Path;

it('builds __PINOOX__ with framework url defaults', function () {
    pinooxBoot();
    App::___()->setLayer(new \Pinoox\Component\Package\AppLayer('/', 'com_pinoox_welcome'));
    Path::___();

    $data = \Pinoox\Component\Helpers\PinooxScriptHelper::bootstrap([
        'locale' => 'fa',
        'url' => ['MANAGER' => '/manager'],
    ]);

    expect($data)
        ->toHaveKey('url')
        ->and($data['url']['BASE'])->not->toBe('')
        ->and($data['url']['API'])->toContain('api')
        ->and($data['url']['MANAGER'])->toBe('/manager')
        ->and($data['locale'])->toBe('fa');
});

it('renders welcome scripts partial with a single __PINOOX__ bootstrap', function () {
    pinooxBoot();
    App::___()->setLayer(new \Pinoox\Component\Package\AppLayer('/', 'com_pinoox_welcome'));
    Path::___();

    $html = render('partials/scripts.twig', [
        'bootstrap' => [
            'locale' => 'fa',
            'direction' => 'rtl',
            'url' => ['MANAGER' => '/manager'],
        ],
    ], exist: false);

    expect($html)
        ->toContain('window.__PINOOX__')
        ->and($html)->toContain('"MANAGER"')
        ->and($html)->toContain('"locale":"fa"')
        ->and($html)->not->toContain('const PINOOX')
        ->and($html)->not->toContain('dist/pinoox.js');
});
