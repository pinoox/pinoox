<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\Config;
use Pinoox\Portal\Lang;

/**
 * Runtime access to another app's resources when it is installed.
 */
final class AppPackageContext
{
    public function __construct(
        private readonly string $package,
        private readonly AppEngine $engine,
    ) {
    }

    public function package(): string
    {
        return $this->package;
    }

    public function exists(): bool
    {
        return $this->engine->exists($this->package);
    }

    public function stable(): bool
    {
        return $this->engine->stable($this->package);
    }

    public function versionCode(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        $appFile = $this->engine->path($this->package, 'app.php');
        if (!is_file($appFile)) {
            return null;
        }

        $data = include $appFile;

        return is_array($data) ? (int) ($data['version-code'] ?? 0) : null;
    }

    public function versionName(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        $appFile = $this->engine->path($this->package, 'app.php');
        if (!is_file($appFile)) {
            return null;
        }

        $data = include $appFile;

        return is_array($data) ? (string) ($data['version-name'] ?? '') : null;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        $this->assertExists();

        return App::meeting($this->package, static function () use ($key, $default) {
            if ($key === null || $key === '') {
                return App::config();
            }

            $parts = explode('.', $key, 2);
            $configName = array_shift($parts);

            if ($configName === null || $configName === '') {
                return $default;
            }

            $config = Config::name($configName);
            $nestedKey = $parts[0] ?? null;

            if ($nestedKey === null || $nestedKey === '') {
                return $config;
            }

            return $config->get($nestedKey, $default);
        });
    }

    public function lang(string $key, array $replace = [], ?string $locale = null): string|array
    {
        $this->assertExists();

        return App::meeting($this->package, static fn () => Lang::get($key, $replace, $locale));
    }

    public function path(string $relative = ''): string
    {
        $this->assertExists();

        $base = rtrim($this->engine->path($this->package), '/\\');

        return $relative === '' ? $base : $base . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    }

    /**
     * Resolve a class inside the dependency app namespace.
     *
     * Examples:
     *   Model.OrderModel  => App\com_shop\Model\OrderModel
     *   Controller/HomeController
     */
    public function class(string $reference): string
    {
        $this->assertExists();

        $reference = trim(str_replace('\\', '/', $reference), '/');
        $reference = str_replace('/', '\\', $reference);

        if (str_starts_with($reference, 'App\\')) {
            return $reference;
        }

        $segments = explode('\\', $reference);
        $root = array_shift($segments);

        if ($root === null || $root === '') {
            throw new Exception('Class reference cannot be empty for package ' . $this->package . '.');
        }

        return 'App\\' . $this->package . '\\' . $root . ($segments !== [] ? '\\' . implode('\\', $segments) : '');
    }

    public function hasAction(string $name): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $name = ltrim($name, '@&');

        return ActionRegistry::has($this->package, $name)
            || ActionRegistry::get($this->package, $this->package . '.' . $name) !== null;
    }

    public function actionUrl(string $name, array $parameters = [], bool $absolute = true): string
    {
        $this->assertExists();

        $name = ltrim($name, '@&');

        return App::url()->actionForPackage($this->package, '@' . $name, $parameters, $absolute);
    }

    /**
     * Execute callback only when the dependency app exists.
     */
    public function when(callable $callback, mixed $default = null): mixed
    {
        if (!$this->exists()) {
            return $default;
        }

        return $callback($this);
    }

    /**
     * Switch active app context temporarily.
     */
    public function meeting(callable $callback): mixed
    {
        $this->assertExists();

        return App::meeting($this->package, $callback);
    }

    private function assertExists(): void
    {
        if (!$this->exists()) {
            throw new Exception('Dependency app is not installed: ' . $this->package);
        }
    }
}

final class AppResource
{
    public static function use(string $package): AppPackageContext
    {
        return new AppPackageContext($package, AppEnginePortal::___());
    }

    public static function parse(string $reference, ?string $defaultPackage = null): AppResourceReference
    {
        return AppResourceReference::parse($reference, $defaultPackage);
    }

    public static function get(string $reference, mixed $default = null, ?string $defaultPackage = null): mixed
    {
        $parsed = self::parse($reference, $defaultPackage);
        $app = self::use($parsed->package);

        if (!$app->exists()) {
            return $default;
        }

        return match ($parsed->type) {
            AppResourceReference::TYPE_CONFIG => $app->config($parsed->value, $default),
            AppResourceReference::TYPE_LANG => $app->lang($parsed->value),
            AppResourceReference::TYPE_PATH => $app->path($parsed->value),
            AppResourceReference::TYPE_CLASS => $app->class($parsed->value),
            AppResourceReference::TYPE_ACTION => $app->actionUrl($parsed->value),
            default => $default,
        };
    }
}
