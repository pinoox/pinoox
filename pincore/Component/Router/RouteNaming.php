<?php

namespace Pinoox\Component\Router;

use Pinoox\Component\Package\App;
use Pinoox\Portal\App\App as AppPortal;
use Pinoox\Portal\App\AppEngine;

class RouteNaming
{
    public static function collectionPrefix(App $app): string
    {
        $package = (string) ($app->package() ?? '');
        if ($package === '') {
            return '';
        }

        try {
            $config = $app->config()->get();
        } catch (\Throwable) {
            $config = [];
        }

        return self::prefixFromConfig($package, is_array($config) ? $config : []);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function prefixFromConfig(string $package, array $config): string
    {
        $router = $config['router'] ?? null;
        if (is_array($router)) {
            $explicit = $router['name'] ?? $router['as'] ?? null;
            if (is_string($explicit) && trim($explicit) !== '') {
                return self::dotted($explicit);
            }
        }

        $slug = self::slugFromPackage($package);
        if ($slug !== '') {
            return self::dotted($slug);
        }

        $name = trim((string) ($config['name'] ?? ''));

        return $name !== '' ? self::dotted($name) : '';
    }

    public static function prefixForPackage(string $package): string
    {
        if ($package === '') {
            return '';
        }

        try {
            return self::prefixFromConfig($package, (array) AppEngine::config($package)->get());
        } catch (\Throwable) {
            return self::dotted(self::slugFromPackage($package));
        }
    }

    public static function currentPrefix(): string
    {
        try {
            return (string) \Pinoox\Portal\Router::___()->currentCollection()->name;
        } catch (\Throwable) {
            try {
                return self::prefixForPackage((string) AppPortal::package());
            } catch (\Throwable) {
                return '';
            }
        }
    }

    public static function localName(string $name, string $prefix): string
    {
        if ($name === '' || $prefix === '') {
            return $name;
        }

        if (str_starts_with($name, $prefix)) {
            return substr($name, strlen($prefix));
        }

        $bare = rtrim($prefix, '.');
        if ($bare !== '' && str_starts_with($name, $bare . '.')) {
            return substr($name, strlen($bare) + 1);
        }

        return $name;
    }

    public static function full(string $name, ?string $package = null): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $prefix = $package !== null
            ? self::prefixForPackage($package)
            : self::currentPrefix();

        if ($prefix === '') {
            return $name;
        }

        $bare = rtrim($prefix, '.');

        if (str_starts_with($name, $prefix) || ($bare !== '' && str_starts_with($name, $bare . '.'))) {
            return $name;
        }

        $first = str_contains($name, '.') ? strstr($name, '.', true) : $name;
        if (is_string($first) && $first !== '' && $first !== $bare && self::isKnownRoutePrefix($first)) {
            return $name;
        }

        return $prefix . $name;
    }

    private static function isKnownRoutePrefix(string $slug): bool
    {
        static $known = null;

        if ($known === null) {
            $known = [];
            try {
                foreach (AppEngine::all() as $package => $manager) {
                    if (!$manager->exists()) {
                        continue;
                    }

                    $bare = rtrim(self::prefixForPackage($package), '.');
                    if ($bare !== '') {
                        $known[$bare] = true;
                    }
                }
            } catch (\Throwable) {
            }
        }

        return isset($known[$slug]);
    }

    public static function slugFromPackage(string $package): string
    {
        if (preg_match('/^com_[^_]+_(.+)$/', $package, $matches) === 1) {
            return (string) $matches[1];
        }

        if (preg_match('/^com_(.+)$/', $package, $matches) === 1) {
            return (string) $matches[1];
        }

        return $package;
    }

    public static function dotted(string $name): string
    {
        $name = trim($name, ". \t\n\r\0\x0B");

        return $name === '' ? '' : $name . '.';
    }
}

