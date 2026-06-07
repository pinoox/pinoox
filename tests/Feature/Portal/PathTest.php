<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Path;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
});

it('declares the Path portal contract', function () {
    expectPortalContract(Path::class);
});

it('resolves system app paths through aliases', function () {
    $basePath = str_replace('\\', '/', dirname(__DIR__, 3));

    expect(Path::___())->toBeInstanceOf(\Pinoox\Component\Path\Path::class)
        ->and(Path::get('~system/config/pinoox.config.php'))
        ->toBe($basePath . '/system/config/pinoox.config.php')
        ->and(Path::root())
        ->toBe($basePath)
        ->and(Path::system('config/pinoox.config.php'))
        ->toBe($basePath . '/system/config/pinoox.config.php');
});

it('resolves named references through resolve()', function () {
    $basePath = str_replace('\\', '/', dirname(__DIR__, 3));

    expect(Path::resolve('~config/app/source.config.php'))
        ->toBe($basePath . '/pincore/config/app/source.config.php');
});

