<?php

use Pinoox\Component\Package\AppLayer;
use Pinoox\Portal\App\App;
use Pinoox\Portal\View;

afterEach(function () {
    try {
        App::___()->setLayer(new AppLayer('/', 'com_pinoox_manager'));
    } catch (\Throwable) {
    }
});

it('registers app-scoped view portal for each package in meeting mode', function () {
    pinooxBoot();
    App::___()->setLayer(new AppLayer('/', 'com_pinoox_manager'));

    View::___();

    expect(View::__has())->toBeTrue();

    $assetUrl = App::meeting('com_pinoox_comingsoon', static function () {
        return assets('assets/images/tehran.jpg');
    }, '/app/com_pinoox_comingsoon');

    expect($assetUrl)
        ->toContain('com_pinoox_comingsoon')
        ->and($assetUrl)->toContain('tehran.jpg')
        ->and(View::__has())->toBeTrue();
});

it('resolves guest theme assets as filesystem paths in meeting mode', function () {
    pinooxBoot();
    App::___()->setLayer(new AppLayer('/', 'com_pinoox_manager'));

    View::___();

    $filesystemPath = App::meeting('com_pinoox_comingsoon', static function () {
        return assets('assets/images/tehran.jpg', true);
    }, '/app/com_pinoox_comingsoon');

    expect($filesystemPath)
        ->toContain('com_pinoox_comingsoon')
        ->and($filesystemPath)->toContain('theme/default/assets/images/tehran.jpg')
        ->and(is_file($filesystemPath))->toBeTrue();
});
