<?php

namespace Pinoox\Portal;

use Pinoox\Component\Package\AppPackageContext;
use Pinoox\Component\Package\AppResource;
use Pinoox\Component\Package\AppResourceReference;
use Pinoox\Component\Source\Portal;

/**
 * @method static AppPackageContext use(string $package)
 * @method static AppResourceReference parse(string $reference, ?string $defaultPackage = null)
 * @method static mixed get(string $reference, mixed $default = null, ?string $defaultPackage = null)
 *
 * @see AppResource
 */
class UseApp extends Portal
{
    public static function __register(): void
    {
    }

    public static function __name(): string
    {
        return 'use_app';
    }

    public static function use(string $package): AppPackageContext
    {
        return AppResource::use($package);
    }

    public static function parse(string $reference, ?string $defaultPackage = null): AppResourceReference
    {
        return AppResource::parse($reference, $defaultPackage);
    }

    public static function get(string $reference, mixed $default = null, ?string $defaultPackage = null): mixed
    {
        return AppResource::get($reference, $default, $defaultPackage);
    }
}

