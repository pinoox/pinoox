<?php

namespace Pinoox\Api;

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

            $file = $manager->path('api/routes.php');
            if (!is_file($file)) {
                continue;
            }

            $config = require $file;
            if (!is_array($config)) {
                continue;
            }

            $entry = $this->normalize($package, $manager->config()->get('developer') ?: $manager->config()->get('name') ?: $package, $config);

            if ($version !== null && $entry['version'] !== $version) {
                continue;
            }

            $apis[$package] = $entry;
        }

        return $apis;
    }

    public function normalize(string $package, string $owner, array $config): array
    {
        $version = trim((string)($config['version'] ?? 'v1'), '/');
        $prefix = trim((string)($config['prefix'] ?? ''), '/');
        $middleware = $this->list($config['middleware'] ?? []);
        $routes = [];

        foreach (($config['routes'] ?? []) as $route) {
            if (!is_array($route)) {
                continue;
            }

            $method = strtoupper((string)($route['method'] ?? 'GET'));
            if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                continue;
            }

            $uri = '/' . trim((string)($route['uri'] ?? '/'), '/');
            $routeMiddleware = array_values(array_unique(array_merge($middleware, $this->list($route['middleware'] ?? []))));
            $routes[] = [
                'app' => $package,
                'owner' => $owner,
                'version' => $version,
                'method' => $method,
                'uri' => $uri,
                'full_uri' => $this->fullUri($package, $version, $prefix, $uri),
                'action' => $route['action'] ?? null,
                'name' => (string)($route['name'] ?? ''),
                'middleware' => $routeMiddleware,
                'permission' => $route['permission'] ?? null,
                'auth' => $route['auth'] ?? null,
                'rate_limit' => $route['rate_limit'] ?? $route['rateLimit'] ?? null,
                'request' => $route['request'] ?? null,
                'resource' => $route['resource'] ?? null,
                'description' => (string)($route['description'] ?? ''),
                'params' => $route['params'] ?? [],
                'body' => $route['body'] ?? [],
                'response' => $route['response'] ?? [],
            ];
        }

        return [
            'app' => $package,
            'owner' => $owner,
            'version' => $version,
            'prefix' => $prefix,
            'middleware' => $middleware,
            'routes' => $routes,
            'docs' => $config['docs'] ?? null,
        ];
    }

    private function fullUri(string $package, string $version, string $prefix, string $uri): string
    {
        $parts = array_filter([
            'api',
            $version,
            'apps',
            $package,
            $prefix,
            trim($uri, '/'),
        ]);

        return '/' . implode('/', $parts);
    }

    private function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? array_values($value) : [$value];
    }
}
