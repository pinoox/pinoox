<?php

namespace Pinoox\PinDoc\Api;

use Pinoox\Component\Router\RouteManifest;
use Pinoox\Component\Router\Router;

class ApiRouteLoader
{
    private array $loaded = [];

    public function __construct(private readonly AppApiRegistry $registry = new AppApiRegistry())
    {
    }

    public function load(Router $router, ?string $app = null, ?string $version = null): array
    {
        $entries = $this->registry->all($app, $version);

        foreach ($entries as $entry) {
            $package = (string)$entry['app'];
            $key = $package . ':' . $entry['version'];
            if (isset($this->loaded[$key])) {
                continue;
            }

            foreach ($entry['routes'] as $route) {
                $flows = RouteManifest::withPermissionFlow(
                    is_array($route['flow'] ?? null) ? $route['flow'] : [],
                    !empty($route['permission']) ? (string) $route['permission'] : null,
                );

                $router->add(
                    path: $route['full_uri'],
                    action: $route['action'],
                    name: $this->routeName($package, $route),
                    methods: [$route['method']],
                    defaults: [
                        '_api' => true,
                        '_api_app' => $package,
                        '_api_version' => $route['version'],
                        '_api_permission' => $route['permission'],
                        '_api_auth' => $route['auth'],
                        '_api_rate_limit' => $route['rate_limit'],
                        '_api_request' => $route['request'],
                        '_api_resource' => $route['resource'],
                    ],
                    data: ['api' => $route] + (!empty($route['permission']) ? ['permission' => $route['permission']] : []),
                    flows: $flows,
                    tags: ['api', 'app-api', $package, $route['version']],
                );
            }

            $this->loaded[$key] = true;
        }

        return $entries;
    }

    private function routeName(string $package, array $route): string
    {
        $name = trim((string)($route['name'] ?? ''));

        return 'api.' . $route['version'] . '.' . $package . ($name !== '' ? '.' . $name : '');
    }
}

