<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Component\Router\Router;

class AppRouteRegistry
{
    /** @var array<string, list<callable>> */

    private static array $web = [];

    /** @var array<string, list<array<string, array|string|\Closure>>> */

    private static array $actions = [];

    public static function absorb(string $package, AppRegisterCollector $collector): void
    {
        if ($collector->webCallbacks !== []) {
            self::$web[$package] = array_merge(self::$web[$package] ?? [], $collector->webCallbacks);
        }

        if ($collector->actions !== []) {
            self::$actions[$package] = array_merge(self::$actions[$package] ?? [], $collector->actions);
        }
    }

    public static function applyWeb(string $package, Router $router): void
    {
        foreach (self::$web[$package] ?? [] as $callback) {
            $callback($router);
        }
    }

    public static function applyActions(string $package, Router $router): void
    {
        foreach (self::$actions[$package] ?? [] as $name => $handler) {
            $router->action($name, $handler);
        }
    }

    public static function has(string $package): bool
    {
        return !empty(self::$web[$package]) || !empty(self::$actions[$package]);
    }

    /**
     * Reset in-memory route registry (tests only).
     */
    public static function reset(): void
    {
        self::$web = [];
        self::$actions = [];
    }
}

