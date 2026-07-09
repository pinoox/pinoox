<?php

function pinoox_normalize_path(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function pinoox_bootstrap_console_utf8(): void
{
    if (PHP_SAPI !== 'cli') {
        return;
    }

    ini_set('default_charset', 'UTF-8');

    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }

    if (PHP_OS_FAMILY === 'Windows' && function_exists('sapi_windows_cp_set')) {
        @sapi_windows_cp_set(65001);
    }
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
        $resolved = pinoox_resolve_relative_core_path($basePath, $configuredPath);
        if (pinoox_is_valid_core_path($resolved)) {
            return $resolved;
        }
        // Local .pincore (e.g. ../pincore3) must not break production vendor installs.
    }

    $localCore = pinoox_detect_local_core_path($basePath);

    if ($localCore !== null) {
        return $localCore;
    }

    return pinoox_default_core_vendor_path($basePath);
}

defined('PINOOX_BASE_PATH') || define('PINOOX_BASE_PATH', pinoox_normalize_path(dirname(__DIR__, 2)));
defined('PINOOX_CORE_PATH') || define('PINOOX_CORE_PATH', pinoox_resolve_configured_core_path(PINOOX_BASE_PATH) . '/');

pinoox_bootstrap_console_utf8();
