<?php

namespace Pinoox\PinDoc\Api;

use Pinoox\Portal\App\App;

class AppApiServiceProvider
{
    /** @var array<string, true> */

    private static array $registered = [];

    public static function resetState(): void
    {
        self::$registered = [];
    }

    public static function register(): array
    {
        $package = App::package();
        if ($package === null || $package === '' || isset(self::$registered[$package])) {
            return [];
        }
        self::$registered[$package] = true;
        return (new ApiRouteLoader())->load(App::router(), $package);
    }
}

