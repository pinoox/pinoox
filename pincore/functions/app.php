<?php

use Pinoox\Component\Package\AppPackageContext;
use Pinoox\Component\Package\AppResource;
use Pinoox\Component\Package\AppDependency;
use Pinoox\Portal\App\AppEngine;

if (!function_exists('use_app')) {
    /**
     * Access another app's config, lang, paths, actions, and classes.
     */
    function use_app(string $package): AppPackageContext
    {
        return AppResource::use($package);
    }
}

if (!function_exists('app_package')) {
    function app_package(string $package): AppPackageContext
    {
        return use_app($package);
    }
}

if (!function_exists('app_resource')) {
    function app_resource(string $reference, mixed $default = null, ?string $defaultPackage = null): mixed
    {
        return AppResource::get($reference, $default, $defaultPackage);
    }
}

if (!function_exists('app_dep_satisfied')) {
    /**
     * @param array<string, mixed>|list<string> $depends
     */
    function app_dep_satisfied(array $depends): bool
    {
        return AppDependency::isSatisfied(AppDependency::normalize($depends), AppEngine::___());
    }
}

