<?php

namespace Pinoox\Support;

class AppRegistry
{
    public static function load(string $file, string $basePath): array
    {
        if (!is_file($file)) {
            return [];
        }

        $config = require $file;

        if (!is_array($config)) {
            return [];
        }

        return self::fromArray($config, $basePath);
    }

    public static function fromArray(array $config, string $basePath): array
    {
        $packages = $config['packages'] ?? $config['apps'] ?? $config;

        if (!is_array($packages)) {
            return [];
        }

        $resolved = [];

        foreach ($packages as $package => $definition) {
            if (!is_string($package)) {
                continue;
            }

            $package = self::normalizePackageName($package);

            if ($package === null) {
                continue;
            }

            $path = self::definitionPath($definition);

            if ($path === null || !self::definitionEnabled($definition)) {
                continue;
            }

            $resolved[$package] = self::resolvePackagePath($path, $basePath);
        }

        return $resolved;
    }

    private static function normalizePackageName(string $package): ?string
    {
        $package = trim($package);

        return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $package) === 1 ? $package : null;
    }

    private static function definitionPath(mixed $definition): ?string
    {
        if (is_string($definition)) {
            return $definition;
        }

        if (is_array($definition) && is_string($definition['path'] ?? null)) {
            return $definition['path'];
        }

        return null;
    }

    private static function definitionEnabled(mixed $definition): bool
    {
        if (!is_array($definition) || !array_key_exists('enabled', $definition)) {
            return true;
        }

        return (bool)$definition['enabled'];
    }

    private static function resolvePackagePath(string $path, string $basePath): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');

        if ($path === '') {
            return $path;
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        if (($systemPath = SystemApp::stripPathAlias($path)) !== null) {
            return SystemApp::path($systemPath);
        }

        if ($path === '~') {
            return $basePath;
        }

        if (str_starts_with($path, '~/')) {
            return $basePath . '/' . ltrim(substr($path, 2), '/');
        }

        if ($path === '~pincore' || str_starts_with($path, '~pincore/')) {
            $suffix = ltrim(substr($path, strlen('~pincore')), '/');

            return SystemApp::legacyCorePath($suffix);
        }

        return SystemConfig::resolvePath($path);
    }
}

