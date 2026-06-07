<?php

namespace Pinoox\Component\AppEvent;

class AppGraphQLRegistryStore
{
    /** @var array<string, list<array<string, mixed>>> */

    private static array $manifests = [];

    public static function absorb(string $package, AppRegisterCollector $collector): void
    {
        if ($collector->graphqlManifests === []) {
            return;
        }

        self::$manifests[$package] = array_merge(self::$manifests[$package] ?? [], $collector->graphqlManifests);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function manifests(string $package): array
    {
        return self::$manifests[$package] ?? [];
    }

    public static function mergeInto(array $base, string $package): array
    {
        foreach (self::manifests($package) as $manifest) {
            $base['types'] = array_merge($base['types'] ?? [], $manifest['types'] ?? []);
            $base['queries'] = array_merge($base['queries'] ?? [], $manifest['queries'] ?? []);
            $base['mutations'] = array_merge($base['mutations'] ?? [], $manifest['mutations'] ?? []);

            if (!empty($manifest['docs'])) {
                $base['docs'] = $manifest['docs'];
            }
        }

        return $base;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function export(string $package): array
    {
        return self::$manifests[$package] ?? [];
    }

    /**
     * @param list<array<string, mixed>> $manifests
     */
    public static function import(string $package, array $manifests): void
    {
        if ($manifests === []) {
            return;
        }

        self::$manifests[$package] = array_merge(self::$manifests[$package] ?? [], $manifests);
    }

    public static function has(string $package): bool
    {
        return !empty(self::$manifests[$package]);
    }
}

