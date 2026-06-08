<?php

namespace Pinoox\Component\Template\Theme;

use Pinoox\Component\Package\AppManifest;
use Pinoox\Portal\App\App;
use Pinoox\Portal\View;

/**
 * Active theme context for the current request (site / panel / kids / ...).
 */
final class ThemeContext
{
    /** @var array<string, string> */

    private static array $active = [];

    public static function active(?string $package = null): ?string
    {
        $package = self::resolvePackage($package);
        $config = self::appConfig($package);

        if (!ThemeContextRegistry::hasContexts($config)) {
            return null;
        }

        return self::$active[$package] ?? ThemeContextRegistry::defaultName($config);
    }

    public static function activate(string $context, ?string $package = null): void
    {
        $package = self::resolvePackage($package);
        $config = self::appConfig($package);

        if (ThemeContextRegistry::hasContexts($config)) {
            if (!in_array($context, ThemeContextRegistry::names($config), true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown theme context "%s" for package "%s".',
                    $context,
                    $package,
                ));
            }
        }

        self::$active[$package] = $context;
        self::applyViewStack($package, $context);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function using(string $context, callable $callback, ?string $package = null): mixed
    {
        $package = self::resolvePackage($package);
        $previous = self::$active[$package] ?? null;

        self::activate($context, $package);

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                unset(self::$active[$package]);
            } else {
                self::$active[$package] = $previous;
            }

            self::applyViewStack($package, self::$active[$package] ?? null);
        }
    }

    public static function reset(?string $package = null): void
    {
        $package = self::resolvePackage($package);
        unset(self::$active[$package]);
        self::applyViewStack($package, null);
    }

    public static function clearAll(): void
    {
        self::$active = [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function info(?string $package = null): array
    {
        $package = self::resolvePackage($package);
        $config = self::appConfig($package);
        $stack = ThemeStack::resolve($package, self::active($package));

        return [
            'package' => $package,
            'context' => self::active($package),
            'contexts' => ThemeContextRegistry::names($config),
            'theme' => $stack['name'],
            'stack' => $stack['stack'],
            'paths' => $stack['paths'],
        ];
    }

    private static function applyViewStack(string $package, ?string $context): void
    {
        try {
            $stack = ThemeStack::resolve($package, $context);
            View::___()->changeTheme($stack['paths']);
            \Pinoox\Component\Dir::setTheme($stack['name'], $stack['path_theme']);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function appConfig(string $package): array
    {
        return AppManifest::load($package);
    }

    private static function resolvePackage(?string $package): string
    {
        if (is_string($package) && $package !== '') {
            return $package;
        }

        try {
            $active = App::package();

            return is_string($active) && $active !== '' ? $active : '';
        } catch (\Throwable) {
            return '';
        }
    }
}

