<?php

namespace Pinoox\Api;

use Pinoox\Portal\Router;

class AppApiServiceProvider
{
    private static bool $registered = false;

    public static function register(): array
    {
        if (self::$registered) {
            return [];
        }

        self::$registered = true;

        return (new ApiRouteLoader())->load(Router::___());
    }
}
