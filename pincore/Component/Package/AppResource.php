<?php

namespace Pinoox\Component\Package;

use Pinoox\Portal\App\AppEngine as AppEnginePortal;

final class AppResource
{
    public static function use(string $package): AppPackageContext
    {
        return new AppPackageContext($package, AppEnginePortal::___());
    }

    public static function parse(string $reference, ?string $defaultPackage = null): AppResourceReference
    {
        return AppResourceReference::parse($reference, $defaultPackage);
    }

    public static function get(string $reference, mixed $default = null, ?string $defaultPackage = null): mixed
    {
        $parsed = self::parse($reference, $defaultPackage);
        $app = self::use($parsed->package);

        if (!$app->exists()) {
            return $default;
        }

        return match ($parsed->type) {
            AppResourceReference::TYPE_CONFIG => $app->config($parsed->value, $default),
            AppResourceReference::TYPE_LANG => $app->lang($parsed->value),
            AppResourceReference::TYPE_PATH => $app->path($parsed->value),
            AppResourceReference::TYPE_CLASS => $app->class($parsed->value),
            AppResourceReference::TYPE_ACTION => $app->actionUrl($parsed->value),
            default => $default,
        };
    }
}
