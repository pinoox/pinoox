<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\App;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Package\Engine\AppEngine as PackageAppEngine;
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Support\AppRegistry;
use Symfony\Component\Routing\RequestContext;

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

it('autoloads external app namespaces registered by the system registry', function () {
    $basePath = str_replace('\\', '/', dirname(__DIR__, 2));
    $package = 'com_test_registry_autoload';
    $externalApp = str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/external_apps/' . $package);
    $controllerDir = $externalApp . '/Controller';
    $controllerClass = 'App\\' . $package . '\\Controller\\RegistryController';

    if (!is_dir($controllerDir)) {
        mkdir($controllerDir, 0777, true);
    }

    file_put_contents($externalApp . '/app.php', "<?php\n\nreturn ['package' => '{$package}', 'name' => 'Registry Autoload', 'enable' => true];\n");
    file_put_contents($controllerDir . '/RegistryController.php', "<?php\n\nnamespace App\\{$package}\\Controller;\n\nclass RegistryController {}\n");

    $packages = [$package => $externalApp];
    $engine = new PackageAppEngine($basePath . '/missing_apps_dir', 'app.php', 'pinker', null, $packages);
    $loader = new Composer\Autoload\ClassLoader();
    $loader->register();

    try {
        new App(
            new AppRouter(new AppRegistryTestConfig([]), $engine, new Request()),
            $engine,
            new RequestContext(),
            $loader,
        );

        expect(class_exists($controllerClass))->toBeTrue();
    } finally {
        $loader->unregister();
    }
});

class AppRegistryTestConfig implements ConfigInterface
{
    public function __construct(private array $data = [])
    {
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->data[$key]);

        return $this;
    }

    public function save(): static
    {
        return $this;
    }
}

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

