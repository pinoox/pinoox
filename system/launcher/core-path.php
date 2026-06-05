<?php

function pinoox_normalize_path(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function pinoox_resolve_configured_core_path(string $basePath): string
{
    $configuredPath = getenv('PINOOX_CORE_PATH') ?: getenv('PINOOX_PINCORE_PATH') ?: null;
    $configFile = $basePath . '/.pincore';

    if (empty($configuredPath) && is_file($configFile)) {
        $configuredPath = trim((string)file_get_contents($configFile));
    }

    $configuredPath = !empty($configuredPath) ? pinoox_normalize_path($configuredPath) : 'pincore';

    if (!preg_match('/^[A-Za-z]:\//', $configuredPath) && !str_starts_with($configuredPath, '/')) {
        $configuredPath = pinoox_normalize_path($basePath . '/' . $configuredPath);
    }

    return $configuredPath;
}

defined('PINOOX_BASE_PATH') || define('PINOOX_BASE_PATH', pinoox_normalize_path(dirname(__DIR__, 2)));
defined('PINOOX_CORE_PATH') || define('PINOOX_CORE_PATH', pinoox_resolve_configured_core_path(PINOOX_BASE_PATH) . '/');
