<?php

namespace Pinoox\Component\Server;

use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Package\Routing\AppRouteMatcher;

/**
 * Pins a single app during `php pinoox serve --app=…` (skips normal app-router matching).
 */
final class ServeAppBinding
{
    public const ENV = 'PINOOX_SERVE_APP';

    public static function isActive(): bool
    {
        return self::value() !== '';
    }

    public static function value(): string
    {
        $value = getenv(self::ENV);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string, string> $routes
     */
    public static function resolveLayer(array $routes, callable $isStable): ?AppLayer
    {
        $binding = self::value();

        if ($binding === '') {
            return null;
        }

        $resolved = self::resolveBinding($binding, $routes);

        if ($resolved === null || !$isStable($resolved['package'])) {
            return null;
        }

        return new AppLayer(
            $resolved['path'],
            $resolved['package'],
            [
                'matched_by' => 'serve_app',
                'serve_binding' => $binding,
            ],
        );
    }

    /**
     * @param array<string, string> $routes Normalized router map (path => package)
     * @return array{package: string, path: string}|null
     */
    public static function resolveBinding(string $binding, array $routes): ?array
    {
        $binding = trim($binding);

        if ($binding === '') {
            return null;
        }

        $routes = AppRouteMatcher::normalizeRoutes($routes);

        if (str_contains($binding, '@')) {
            [$package, $path] = explode('@', $binding, 2);
            $package = trim($package);
            $path = AppRouteMatcher::normalize(trim($path));

            if ($package === '') {
                return null;
            }

            return [
                'package' => $package,
                'path' => $path,
            ];
        }

        if (str_starts_with($binding, 'com_')) {
            return [
                'package' => $binding,
                'path' => '/',
            ];
        }

        $routePath = AppRouteMatcher::normalize(
            str_starts_with($binding, '/') ? $binding : '/' . $binding,
        );

        if (isset($routes[$routePath]) && is_string($routes[$routePath])) {
            return [
                'package' => $routes[$routePath],
                'path' => $routePath,
            ];
        }

        foreach ($routes as $path => $package) {
            if (!is_string($package)) {
                continue;
            }

            if ($package === $binding) {
                return [
                    'package' => $package,
                    'path' => self::normalizeRouteKey($path),
                ];
            }
        }

        $guessedPackage = str_contains($binding, '_')
            ? $binding
            : 'com_pinoox_' . ltrim($binding, '/');

        if (str_starts_with($guessedPackage, 'com_')) {
            return [
                'package' => $guessedPackage,
                'path' => '/',
            ];
        }

        return null;
    }

    private static function normalizeRouteKey(string $path): string
    {
        if ($path === '*' || $path === '/') {
            return '/';
        }

        return AppRouteMatcher::normalize($path);
    }
}
