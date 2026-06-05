<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\Engine\AppEngine as PackageAppEngine;
use Pinoox\Support\AppRegistry;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 2));
});

afterEach(function () {
    deleteAppRegistryTestDirectory(dirname(__DIR__) . '/Fixtures/external_apps');
});

it('registers external apps from the system app registry config', function () {
    $basePath = str_replace('\\', '/', dirname(__DIR__, 2));
    $package = 'com_test_registry';
    $externalApp = str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/external_apps/' . $package);

    if (!is_dir($externalApp)) {
        mkdir($externalApp, 0777, true);
    }

    file_put_contents($externalApp . '/app.php', "<?php\n\nreturn ['package' => '{$package}', 'name' => 'Registry Test', 'enable' => true];\n");

    $registryFile = str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/app_registry.config.php');

    try {
        file_put_contents($registryFile, "<?php\n\nreturn ['packages' => ['{$package}' => 'tests/Fixtures/external_apps/{$package}']];\n");

        $packages = AppRegistry::load($registryFile, $basePath);
        $engine = new PackageAppEngine($basePath . '/missing_apps_dir', 'app.php', 'pinker', null, $packages);

        expect($packages[$package])->toBe($externalApp)
            ->and($engine->exists($package))->toBeTrue()
            ->and($engine->path($package))->toBe($externalApp)
            ->and($engine->config($package)->get('name'))->toBe('Registry Test');
    } finally {
        @unlink($registryFile);
    }
});

function deleteAppRegistryTestDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}
