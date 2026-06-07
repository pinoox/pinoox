<?php

namespace Pinoox\Component\AppEvent;

class AppApiRegistryStore
{
    /** @var array<string, list<array<string, mixed>>> */

    private static array $manifests = [];

    /** @var array<string, list<array<string, mixed>>> */

    private static array $routes = [];

    public static function absorb(string $package, AppRegisterCollector $collector): void
    {
        if ($collector->apiManifests !== []) {
            self::$manifests[$package] = array_merge(self::$manifests[$package] ?? [], $collector->apiManifests);
        }

        if ($collector->apiRoutes !== []) {
            self::$routes[$package] = array_merge(self::$routes[$package] ?? [], $collector->apiRoutes);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function manifests(string $package): array
    {
        return self::$manifests[$package] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function routes(string $package): array
    {
        return self::$routes[$package] ?? [];
    }

    public static function has(string $package): bool
    {
        return !empty(self::$manifests[$package]) || !empty(self::$routes[$package]);
    }

    /**
     * @return array{manifests: list<array<string, mixed>>, routes: list<array<string, mixed>>}
     */
    public static function export(string $package): array
    {
        return [
            'manifests' => self::$manifests[$package] ?? [],
            'routes' => self::$routes[$package] ?? [],
        ];
    }

    /**
     * @param array{manifests?: list<array<string, mixed>>, routes?: list<array<string, mixed>>} $payload
     */
    public static function import(string $package, array $payload): void
    {
        if (!empty($payload['manifests'])) {
            self::$manifests[$package] = array_merge(self::$manifests[$package] ?? [], $payload['manifests']);
        }

        if (!empty($payload['routes'])) {
            self::$routes[$package] = array_merge(self::$routes[$package] ?? [], $payload['routes']);
        }
    }
}

