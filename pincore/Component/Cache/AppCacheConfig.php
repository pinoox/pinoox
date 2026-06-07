<?php

namespace Pinoox\Component\Cache;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Mode;

class AppCacheConfig
{

    public const MODE_DEVELOPMENT = 'development';

    public const MODE_PRODUCTION = 'production';
    /**
     * @return array{
     *     enabled: bool,
     *     mode: string,
     *     package: string,
     *     stores: array<string, bool>,
     *     build_stores: array<string, bool>,
     *     twig: array<string, mixed>
     * }
     */
    public static function resolve(?string $package = null): array
    {
        $package = $package ?? (string) (App::package() ?? '');
        $config = self::readConfig($package);
        $mode = self::resolveCacheMode($package, $config);
        $production = $mode === self::MODE_PRODUCTION;
        $enabled = array_key_exists('enabled', $config) && $config['enabled'] !== null
            ? (bool) $config['enabled']
            : false;
        $stores = is_array($config['stores'] ?? null)
            ? array_merge(self::defaultStores(true), $config['stores'])
            : self::defaultStores(true);
        return [
            'enabled' => $enabled,
            'mode' => $production ? self::MODE_PRODUCTION : self::MODE_DEVELOPMENT,
            'package' => $package,
            'stores' => $stores,
            'build_stores' => self::storesForBuild($package),
            'twig' => is_array($config['twig'] ?? null) ? $config['twig'] : [],
        ];
    }
    /**
     * Runtime cache is opt-in — only when app.php sets cache.enabled => true.
     */
    public static function enabled(?string $package = null): bool
    {
        return self::resolve($package)['enabled'];
    }

    public static function storeEnabled(string $store, ?string $package = null): bool
    {
        $config = self::resolve($package);
        return $config['enabled'] && !empty($config['stores'][$store]);
    }
    /**
     * Stores to build during cache:build / .pinx install (independent of runtime enabled).
     *
     * @return array<string, bool>
     */
    public static function storesForBuild(?string $package = null): array
    {
        $package = $package ?? (string) (App::package() ?? '');
        $config = self::readConfig($package);
        $mode = self::resolveCacheMode($package, $config);
        if (is_array($config['build']['stores'] ?? null)) {
            return array_merge(self::defaultStores(false), $config['build']['stores']);
        }
        $configured = is_array($config['stores'] ?? null)
            ? array_merge(self::defaultStores(true), $config['stores'])
            : self::defaultStores(true);
        if ($mode === self::MODE_PRODUCTION) {
            return $configured;
        }
        return array_merge(self::defaultStores(false), ['pinker' => true]);
    }

    public static function storeEnabledForBuild(string $store, ?string $package = null): bool
    {
        $stores = self::storesForBuild($package);
        return !empty($stores[$store]);
    }
    /**
     * @return array<string, mixed>
     */
    public static function twigOptions(?string $package = null): array
    {
        $config = self::resolve($package);
        $options = $config['twig'];
        $production = $config['mode'] === self::MODE_PRODUCTION;
        if ($config['enabled'] && !empty($config['stores']['twig']) && $package !== '') {
            $cacheDir = AppCachePath::twig($package);
            $options['cache'] = $options['cache'] ?? $cacheDir;
            $options['auto_reload'] = $options['auto_reload'] ?? !$production;
        }
        if ($production) {
            $options['debug'] = $options['debug'] ?? false;
        }
        return $options;
    }
    /**
     * @return array<string, bool>
     */
    private static function defaultStores(bool $enabled): array
    {
        return [
            'routes' => $enabled,
            'api' => $enabled,
            'boot' => $enabled,
            'twig' => $enabled,
            'graphql' => $enabled,
            'pinker' => $enabled,
        ];
    }
    /**
     * @return array<string, mixed>
     */
    private static function readConfig(string $package): array
    {
        if ($package === '') {
            return [];
        }
        try {
            $config = AppEngine::config($package)->get('cache') ?? [];
        } catch (\Throwable) {
            $config = [];
        }
        return is_array($config) ? $config : [];
    }

    private static function resolveCacheMode(string $package, array $config): string
    {
        if (!empty($config['mode'])) {
            $mode = RuntimeMode::normalize((string) $config['mode']);
            if (in_array($mode, [self::MODE_PRODUCTION, self::MODE_DEVELOPMENT], true)) {
                return $mode;
            }
        }
        try {
            return Mode::cacheMode($package !== '' ? $package : null);
        } catch (\Throwable) {
            $global = RuntimeMode::readGlobal()['mode'];
            return in_array($global, [self::MODE_PRODUCTION, 'prod', RuntimeMode::STAGING], true)
                ? self::MODE_PRODUCTION
                : self::MODE_DEVELOPMENT;
        }
    }
}

