<?php

use Pinoox\Component\Package\Engine\DelegatingEngine;
use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\Config;
use Pinoox\Component\Store\Config\Data\DataManager;
use Pinoox\Component\Store\Config\Strategy\ConfigStrategyInterface;
use Pinoox\Component\Translator\Translator;

it('checks stable status from the target package config, not the active app', function () {
    $engine = new AppHmvcTestEngine([
        'com_enabled_app' => true,
        'com_disabled_app' => false,
    ]);

    expect($engine->stable('com_enabled_app'))->toBeTrue()
        ->and($engine->stable('com_disabled_app'))->toBeFalse()
        ->and($engine->stable('com_missing_app'))->toBeFalse();
});

it('delegates engine methods through DelegatingEngine', function () {
    $inner = new AppHmvcTestEngine(['com_demo' => true], '/apps');
    $engine = new DelegatingEngine([$inner]);

    expect($engine->stable('com_demo'))->toBeTrue()
        ->and($engine->path('com_demo', 'lang'))->toBe('/apps/com_demo/lang')
        ->and($engine->exists('com_demo'))->toBeTrue();
});

class AppHmvcTestEngine implements EngineInterface
{
    /** @param array<string, bool> $enabled */

    public function __construct(
        private array $enabled = [],
        private string $basePath = '/apps',
    ) {
    }

    public function config(string|ReferenceInterface $packageName): \Pinoox\Component\Store\Config\ConfigInterface
    {
        $package = is_string($packageName) ? $packageName : $packageName->getPackageName();

        return new Config(new AppHmvcArrayConfigStrategy([
            'enable' => $this->enabled[$package] ?? false,
        ]));
    }

    public function lang(string|ReferenceInterface $packageName): Translator
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function router(string|ReferenceInterface $packageName, string $path = ''): Router
    {
        throw new RuntimeException('Not needed in this test.');
    }

    public function exists(string|ReferenceInterface $packageName): bool
    {
        $package = is_string($packageName) ? $packageName : $packageName->getPackageName();

        return array_key_exists($package, $this->enabled);
    }

    public function stable(string|ReferenceInterface $packageName): bool
    {
        $package = is_string($packageName) ? $packageName : $packageName->getPackageName();

        if (!$this->exists($package)) {
            return false;
        }

        return (bool) $this->config($package)->get('enable');
    }

    public function supports(string|ReferenceInterface $packageName): bool
    {
        return $this->exists($packageName);
    }

    public function path(string|ReferenceInterface $packageName, string $path = ''): string
    {
        $package = is_string($packageName) ? $packageName : $packageName->getPackageName();
        $base = rtrim($this->basePath, '/') . '/' . $package;

        return $path === '' ? $base : $base . '/' . trim($path, '/');
    }
}

class AppHmvcArrayConfigStrategy implements ConfigStrategyInterface
{
    private DataManager $data;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->data = new DataManager($config);
    }

    public function save(): void
    {
    }

    public function set(string $key, mixed $value): void
    {
        $this->data->set($key, $value);
    }

    public function add(string $key, mixed $value): void
    {
        $this->data->add($key, $value);
    }

    public function get(?string $key = null, $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    public function all(): mixed
    {
        return $this->data->all();
    }

    public function getInfo(?string $key = null): array|string|null
    {
        return null;
    }

    public function remove(string $key): void
    {
        $this->data->remove($key);
    }

    public function reset(): void
    {
        $this->data = new DataManager([]);
    }

    public function merge(array $array): void
    {
        $this->data->merge($array);
    }

    public function name(): string
    {
        return 'array';
    }
}

