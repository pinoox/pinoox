<?php

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Portal\Mode;

if (!function_exists('runtime_env_mode')) {
    function runtime_env_mode(?string $default = null): string
    {
        return RuntimeMode::fromEnv($default);
    }
}

if (!function_exists('runtime_mode')) {
    function runtime_mode(?string $package = null): string
    {
        return Mode::name($package);
    }
}

if (!function_exists('runtime_debug')) {
    function runtime_debug(?string $package = null): bool
    {
        return Mode::debug($package);
    }
}

if (!function_exists('runtime_production')) {
    function runtime_production(?string $package = null): bool
    {
        return Mode::isProduction($package);
    }
}

