<?php

namespace Pinoox\PinDoc\Api;

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\Cache\Store\ApiCacheStore;
use Pinoox\PinDoc\AppDocProfile;
use Pinoox\Portal\App\AppEngine;

class AppApiRegistry
{
    public function all(?string $app = null, ?string $version = null): array
    {
        $apis = [];

        foreach (AppEngine::all() as $package => $manager) {
            if ($app !== null && $package !== $app) {
                continue;
            }

            $cached = ApiCacheStore::loadEntries($package);
            if ($cached !== null) {
                foreach ($cached as $key => $entry) {
                    if ($version !== null && ($entry['version'] ?? null) !== $version) {
                        continue;
                    }
                    $apis[$key] = $entry;
                }
                continue;
            }

            $files = $this->routeFiles($manager);
            AppBootstrap::ensure($package);
            $bootManifests = AppBootstrap::apiManifests($package);

            if ($files === [] && $bootManifests === []) {
                continue;
            }

            $owner = AppDocProfile::fromPackage($package)['developer'];
            $appMeta = AppDocProfile::fromPackage($package);
            $byVersion = [];

            foreach ($files as $file) {
                $config = require $file;
                if (!is_array($config)) {
                    continue;
                }

                $normalized = $this->normalize($package, $owner, $config);
                $entryVersion = (string)$normalized['version'];

                if ($version !== null && $entryVersion !== $version) {
                    continue;
                }

                $byVersion[$entryVersion] = isset($byVersion[$entryVersion])
                    ? $this->mergeEntries($byVersion[$entryVersion], $normalized)
                    : $normalized;
            }

            foreach ($bootManifests as $config) {
                $normalized = $this->normalize($package, $owner, $config);
                $entryVersion = (string)$normalized['version'];

                if ($version !== null && $entryVersion !== $version) {
                    continue;
                }

                $byVersion[$entryVersion] = isset($byVersion[$entryVersion])
                    ? $this->mergeEntries($byVersion[$entryVersion], $normalized)
                    : $normalized;
            }

            if ($byVersion === []) {
                continue;
            }

            ksort($byVersion);

            foreach ($byVersion as $entryVersion => $entry) {
                $key = count($byVersion) === 1 || $version !== null
                    ? $package
                    : $package . ':' . $entryVersion;

                $apis[$key] = array_merge($entry, ['app_meta' => $appMeta]);
            }
        }

        return $apis;
    }

    public function firstEntry(array $entries, string $package): ?array
    {
        if (isset($entries[$package])) {
            return $entries[$package];
        }

        foreach ($entries as $entry) {
            if (($entry['app'] ?? '') === $package) {
                return $entry;
            }
        }

        return null;
    }

    public function normalize(string $package, string $owner, array $config): array
    {
        $version = trim((string)($config['version'] ?? 'v1'), '/');
        $prefix = trim((string)($config['prefix'] ?? ''), '/');
        $flow = $this->list($config['flow'] ?? []);
        $routes = [];

        foreach (($config['routes'] ?? []) as $route) {
            if (!is_array($route)) {
                continue;
            }

            $method = strtoupper((string)($route['method'] ?? 'GET'));
            if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                continue;
            }

            $uri = '/' . trim((string)($route['path'] ?? $route['uri'] ?? '/'), '/');
            $routeFlow = array_values(array_unique(array_merge($flow, $this->list($route['flow'] ?? []))));
            $routes[] = [
                'app' => $package,
                'owner' => $owner,
                'version' => $version,
                'method' => $method,
                'uri' => $uri,
                'full_uri' => $this->fullUri($version, $prefix, $uri),
                'action' => $route['action'] ?? null,
                'name' => (string)($route['name'] ?? ''),
                'flow' => $routeFlow,
                'permission' => $route['permission'] ?? null,
                'auth' => $route['auth'] ?? null,
                'rate_limit' => $route['rate_limit'] ?? $route['rateLimit'] ?? null,
                'request' => $route['request'] ?? null,
                'resource' => $route['resource'] ?? null,
                'description' => (string)($route['description'] ?? ''),
                'summary' => (string)($route['summary'] ?? ''),
                'tag' => (string)($route['tag'] ?? ''),
                'deprecated' => (bool)($route['deprecated'] ?? false),
                'params' => $route['params'] ?? [],
                'body' => $route['body'] ?? [],
                'body_description' => (string)($route['body_description'] ?? ''),
                'body_example' => $route['body_example'] ?? null,
                'response' => $route['response'] ?? [],
                'responses' => $route['responses'] ?? [],
            ];
        }

        return [
            'app' => $package,
            'owner' => $owner,
            'app_meta' => AppDocProfile::fromPackage($package),
            'version' => $version,
            'prefix' => $prefix,
            'flow' => $flow,
            'routes' => $routes,
            'docs' => $config['docs'] ?? null,
        ];
    }

    private function fullUri(string $version, string $prefix, string $uri): string
    {
        $parts = array_filter([
            'api',
            $version,
            $prefix,
            trim($uri, '/'),
        ]);

        return '/' . implode('/', $parts);
    }

    private function routeFiles(object $manager): array
    {
        $files = [];
        $routesDir = $manager->path('routes');
        $main = $routesDir . '/api.php';

        if (is_file($main)) {
            $files[] = $main;
        }

        foreach (glob($routesDir . '/api-v*.php') ?: [] as $file) {
            if (!in_array($file, $files, true)) {
                $files[] = $file;
            }
        }

        sort($files);

        return $files;
    }

    private function mergeEntries(array $base, array $entry): array
    {
        if ($base['version'] !== $entry['version']) {
            return $base;
        }

        $base['routes'] = array_merge($base['routes'], $entry['routes']);
        $base['flow'] = array_values(array_unique(array_merge($base['flow'], $entry['flow'])));

        if (!empty($entry['docs'])) {
            $base['docs'] = $entry['docs'];
        }

        return $base;
    }

    private function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? array_values($value) : [$value];
    }
}

