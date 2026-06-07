<?php

use Pinoox\Portal\AppCache;

if (!function_exists('app_cache_build')) {
    function app_cache_build(?string $package = null, ?array $only = null, bool $force = false): array
    {
        return AppCache::build($package, $only, $force);
    }
}

