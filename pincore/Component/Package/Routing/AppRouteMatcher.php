<?php

namespace Pinoox\Component\Package\Routing;

final class AppRouteMatcher
{
    /**
     * @param array<string, string> $routes
     * @param callable(string): bool|null $isStable
     * @return array{path: string, package: string}|null
     */
    public static function match(string $pathInfo, array $routes, ?callable $isStable = null): ?array
    {
        $pathInfo = self::normalize($pathInfo);

        if ($routes === []) {
            return null;
        }

        $candidates = [];

        foreach ($routes as $routePath => $package) {
            if (!is_string($routePath) || !is_string($package)) {
                continue;
            }

            if (in_array($routePath, ['*', '/'], true)) {
                continue;
            }

            $routePath = self::normalize($routePath);
            $candidates[$routePath] = $package;
        }

        uksort($candidates, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($candidates as $routePath => $package) {
            if ($isStable !== null && $isStable($package) === false) {
                continue;
            }

            if ($pathInfo === $routePath || str_starts_with($pathInfo, rtrim($routePath, '/') . '/')) {
                return [
                    'path' => $routePath,
                    'package' => $package,
                ];
            }
        }

        return null;
    }

    public static function normalize(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }

    /**
     * @param array<string, string> $routes
     */
    public static function normalizeRoutes(array $routes): array
    {
        $normalized = [];

        foreach ($routes as $path => $package) {
            if (!is_string($path) || !is_string($package)) {
                continue;
            }

            $key = $path === '*' ? '*' : self::normalize($path);
            $normalized[$key] = $package;
        }

        return $normalized;
    }
}

