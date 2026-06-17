<?php

namespace App\com_pinoox_manager\Component;

class AppRoutePolicy
{
    public const MODE_SINGLE = 'single';

    public const MODE_MULTIPLE = 'multiple';

    public static function resolveMode(mixed $routerConfig): string
    {
        if (is_string($routerConfig)) {
            return $routerConfig === self::MODE_SINGLE
                ? self::MODE_SINGLE
                : self::MODE_MULTIPLE;
        }

        if (is_array($routerConfig)) {
            $type = $routerConfig['type'] ?? $routerConfig['mode'] ?? null;

            if ($type === self::MODE_SINGLE) {
                return self::MODE_SINGLE;
            }

            return self::MODE_MULTIPLE;
        }

        return self::MODE_MULTIPLE;
    }

    public static function allowsMultiple(mixed $routerConfig): bool
    {
        return self::resolveMode($routerConfig) !== self::MODE_SINGLE;
    }

    public static function isRoutable(mixed $routerConfig): bool
    {
        return !empty($routerConfig);
    }
}
