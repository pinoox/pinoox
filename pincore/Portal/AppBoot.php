<?php

namespace Pinoox\Portal;

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppRegister;
use Pinoox\Component\Source\Portal;

/**
 * App boot / event-route registration.
 *
 * @method static AppRegister ensure(?string $package = null, bool $integrate = false)
 * @method static void integrate(string $package)
 * @method static bool booted(string $package)
 * @method static void applyRoutes(string $package, \Pinoox\Component\Router\Router $router)
 * @method static list<array<string, mixed>> apiManifests(string $package)
 * @method static void bootGlobalApps(bool $integrate = false)
 * @method static list<string> globalBootPackages()
 * @method static void resetState()
 * @method static AppBootstrap ___()
 *
 * @see AppBootstrap
 */
class AppBoot extends Portal
{
    public static function __register(): void
    {
        self::__bind(AppBootstrap::class);
    }

    public static function __name(): string
    {
        return 'app.boot';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [
            'booted',
            'ensure',
            'integrate',
            'applyRoutes',
            'apiManifests',
            'bootGlobalApps',
            'globalBootPackages',
            'resetState',
        ];
    }
}

