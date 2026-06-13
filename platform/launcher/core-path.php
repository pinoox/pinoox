<?php

function pinoox_normalize_path(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function pinoox_is_valid_core_path(string $path): bool
{
    $path = pinoox_normalize_path($path);

    return is_file($path . '/functions/base.php')
        || is_file($path . '/launcher/bootstrap.php');
}

function pinoox_resolve_relative_core_path(string $basePath, string $configuredPath): string
{
    $configuredPath = pinoox_normalize_path($configuredPath);

    if (!preg_match('/^[A-Za-z]:\//', $configuredPath) && !str_starts_with($configuredPath, '/')) {
        $configuredPath = pinoox_normalize_path($basePath . '/' . $configuredPath);
    }

    return $configuredPath;
}

function pinoox_detect_local_core_path(string $basePath): ?string
{
    $basePath = pinoox_normalize_path($basePath);
    $localCore = $basePath . '/pincore';

    if (pinoox_is_valid_core_path($localCore)) {
        return $localCore;
    }

    return null;
}

function pinoox_default_core_vendor_path(string $basePath): string
{
    return pinoox_normalize_path($basePath . '/vendor/pinoox/pincore');
}

function pinoox_resolve_configured_core_path(string $basePath): string
{
    $configuredPath = getenv('PINOOX_CORE_PATH') ?: null;
    $configFile = $basePath . '/.pincore';

    if (empty($configuredPath) && is_file($configFile)) {
        $configuredPath = trim((string)file_get_contents($configFile));
    }

    if (!empty($configuredPath)) {
        return pinoox_resolve_relative_core_path($basePath, $configuredPath);
    }

    $localCore = pinoox_detect_local_core_path($basePath);

    if ($localCore !== null) {
        return $localCore;
    }

    return pinoox_default_core_vendor_path($basePath);
}

defined('PINOOX_BASE_PATH') || define('PINOOX_BASE_PATH', pinoox_normalize_path(dirname(__DIR__, 2)));
defined('PINOOX_CORE_PATH') || define('PINOOX_CORE_PATH', pinoox_resolve_configured_core_path(PINOOX_BASE_PATH) . '/');
