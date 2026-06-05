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
        ->and(Path::get('~system/config/test.config.php'))
        ->toBe($basePath . '/system/config/test.config.php');
});
