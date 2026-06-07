<?php

namespace Pinoox\Component\Router\Action;

use Pinoox\Component\Router\RouteSourceRegistry;
use Pinoox\Component\Router\Router;

class ActionRegistry
{
    /** @var array<string, array<string, ActionDefinition>> */

    private static array $definitions = [];

    /** @var array<string, array<string, string>> routeName => actionKey */

    private static array $routeLinks = [];

    public static function reset(): void
    {
        self::$definitions = [];
        self::$routeLinks = [];
    }

    public static function register(string $package, ActionDefinition $definition): void
    {
        self::$definitions[$package][$definition->name] = $definition;
    }

    public static function get(string $package, string $name): ?ActionDefinition
    {
        return self::$definitions[$package][$name] ?? null;
    }

    public static function has(string $package, string $name): bool
    {
        return isset(self::$definitions[$package][$name]);
    }

    /** @return array<string, ActionDefinition> */

    public static function all(?string $package = null): array
    {
        if ($package !== null) {
            return self::$definitions[$package] ?? [];
        }

        return array_merge(...array_values(self::$definitions ?: [[]]));
    }

    /** @return array<string, array<string, ActionDefinition>> */

    public static function groupedByPackage(): array
    {
        return self::$definitions;
    }

    public static function linkRoute(string $package, string $routeName, string $actionKey, string $path, string $reference): void
    {
        self::$routeLinks[$package][$routeName] = $actionKey;

        $definition = self::$definitions[$package][$actionKey] ?? null;
        if ($definition instanceof ActionDefinition) {
            $definition->addRoute($routeName, $path);
        }
    }

    public static function routeForAction(string $package, string $actionKey): ?string
    {
        foreach (self::$routeLinks[$package] ?? [] as $routeName => $linkedAction) {
            if ($linkedAction === $actionKey) {
                return $routeName;
            }
        }

        return null;
    }

    /** @return list<string> */

    public static function routesForAction(string $package, string $actionKey): array
    {
        $routes = [];
        foreach (self::$routeLinks[$package] ?? [] as $routeName => $linkedAction) {
            if ($linkedAction === $actionKey) {
                $routes[] = $routeName;
            }
        }

        return $routes;
    }

    public static function syncFromRouter(string $package, Router $router): void
    {
        foreach ($router->actions as $name => $handler) {
            if (self::has($package, $name)) {
                continue;
            }

            $source = RouteSourceRegistry::action($name) ?? [];

            self::register($package, new ActionDefinition(
                name: $name,
                handler: $handler,
                declared: (string) ($source['declared'] ?? ''),
                file: isset($source['file']) ? (string) $source['file'] : null,
                line: isset($source['line']) ? (int) $source['line'] : null,
                relativeFile: isset($source['relative_file']) ? (string) $source['relative_file'] : null,
                handlerRef: ActionHandlerRef::encode($handler),
            ));
        }
    }

    /** @return list<array<string, mixed>> */

    public static function exportManifest(string $package): array
    {
        $manifest = [];
        foreach (self::$definitions[$package] ?? [] as $definition) {
            $manifest[] = $definition->toArray();
        }

        return $manifest;
    }

    public static function importManifest(string $package, array $manifest, bool $mergeRuntimeHandlers = false): void
    {
        foreach ($manifest as $entry) {
            if (!is_array($entry) || empty($entry['name'])) {
                continue;
            }

            $name = (string) $entry['name'];
            $existing = self::get($package, $name);
            $handlerRef = is_array($entry['handler_ref'] ?? null) ? $entry['handler_ref'] : null;
            $handler = ActionHandlerRef::decode($handlerRef);

            if ($mergeRuntimeHandlers && $existing !== null) {
                if ($existing->handler !== null) {
                    $handler = $existing->handler;
                }

                if (self::isInfrastructureSourcePath((string) ($entry['file'] ?? '')) && $existing->relativeFile) {
                    $entry['file'] = $existing->relativeFile;
                    $entry['line'] = $existing->line;
                }
            }

            $definition = new ActionDefinition(
                name: $name,
                handler: $handler,
                declared: (string) ($entry['handler'] ?? ''),
                description: (string) ($entry['description'] ?? ''),
                flows: (array) ($entry['flows'] ?? []),
                tags: (array) ($entry['tags'] ?? []),
                relativeFile: isset($entry['file']) ? (string) $entry['file'] : null,
                line: isset($entry['line']) ? (int) $entry['line'] : null,
                group: isset($entry['group']) ? (string) $entry['group'] : null,
                handlerRef: $handlerRef,
            );

            foreach ((array) ($entry['routes'] ?? []) as $routeName) {
                $definition->addRoute((string) $routeName, '');
            }

            self::register($package, $definition);
        }
    }

    private static function isInfrastructureSourcePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return str_contains($path, 'Component/Source/Portal.php')
            || str_contains($path, 'Component/Router/Router.php')
            || str_contains($path, 'functions/router.php');
    }
}

