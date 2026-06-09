<?php

namespace Pinoox\Component\Kernel\Debug\Support;

use Symfony\Component\ErrorHandler\Exception\FlattenException;

class TraceFrameClassifier
{
    public static function isPortalError(?FlattenException $exception, ?array $portalContext = null): bool
    {
        if (!empty($portalContext['via_portal'])) {
            return true;
        }

        if ($exception === null) {
            return false;
        }

        return str_contains(self::normalizePath($exception->getFile()), '/Component/Source/Portal.php');
    }

    public static function isPincore(array $trace): bool
    {
        $file = self::normalizePath((string) ($trace['file'] ?? ''));

        return $file !== '' && str_contains($file, '/pincore/');
    }

    public static function isSystemPath(string $file, ?string $projectRoot = null): bool
    {
        $file = self::normalizePath($file);

        if ($file === '') {
            return false;
        }

        if ($projectRoot !== null && $projectRoot !== '') {
            $root = rtrim(self::normalizePath($projectRoot), '/');

            return str_starts_with($file, $root.'/config/')
                || str_starts_with($file, $root.'/system/')
                || str_starts_with($file, $root.'/launcher/');
        }

        return (str_contains($file, '/config/') || str_contains($file, '/system/') || str_contains($file, '/launcher/'))
            && !str_contains($file, '/vendor/')
            && !str_contains($file, '/apps/');
    }

    public static function isSystem(array $trace, ?string $projectRoot = null): bool
    {
        return self::isSystemPath((string) ($trace['file'] ?? ''), $projectRoot);
    }

    public static function isProjectEntryPath(string $file, ?string $projectRoot = null): bool
    {
        $file = self::normalizePath($file);

        if ($file === '' || !str_ends_with($file, '/index.php')) {
            return false;
        }

        if ($projectRoot !== null && $projectRoot !== '') {
            $root = rtrim(self::normalizePath($projectRoot), '/');

            return $file === $root.'/index.php';
        }

        return !str_contains($file, '/vendor/')
            && !str_contains($file, '/apps/')
            && !str_contains($file, '/node_modules/');
    }

    public static function isProjectEntry(array $trace, ?string $projectRoot = null): bool
    {
        return self::isProjectEntryPath((string) ($trace['file'] ?? ''), $projectRoot);
    }

    public static function isFrameworkFrame(array $trace, ?string $projectRoot = null): bool
    {
        return self::isPincore($trace)
            || self::isSystem($trace, $projectRoot)
            || self::isProjectEntry($trace, $projectRoot);
    }

    public static function isPortalCore(array $trace): bool
    {
        return str_contains(self::normalizePath((string) ($trace['file'] ?? '')), '/Component/Source/Portal.php');
    }

    public static function isVendor(array $trace): bool
    {
        $file = self::normalizePath((string) ($trace['file'] ?? ''));

        return $file !== '' && (str_contains($file, '/vendor/') || str_contains($file, '/var/cache/'));
    }

    public static function isHiddenFrame(array $trace, ?string $projectRoot = null): bool
    {
        return self::isFrameworkFrame($trace, $projectRoot) || self::isVendor($trace);
    }

    public static function isUserCodeFrame(array $trace, ?string $projectRoot = null): bool
    {
        return !self::isHiddenFrame($trace, $projectRoot) && !empty($trace['file']);
    }

    public static function findOriginIndex(array $traces, ?string $projectRoot = null): ?int
    {
        foreach ($traces as $index => $trace) {
            if (self::isHiddenFrame($trace, $projectRoot) || empty($trace['file'])) {
                continue;
            }

            return $index;
        }

        return null;
    }

    public static function lineClasses(array $trace, ?int $originIndex, int $index, ?string $projectRoot = null): string
    {
        $classes = [];

        if (self::isVendor($trace)) {
            $classes[] = 'trace-from-vendor';
        }

        if (self::isFrameworkFrame($trace, $projectRoot)) {
            $classes[] = 'trace-from-pincore';
        }

        if (self::isPortalCore($trace)) {
            $classes[] = 'trace-from-portal-core';
        }

        if ($originIndex === $index) {
            $classes[] = 'trace-portal-origin';
        }

        return implode(' ', $classes);
    }

    public static function displayStyle(array $trace, ?int $originIndex, int $index, bool &$isFirstUserCode, ?string $projectRoot = null): string
    {
        if ($originIndex === $index) {
            return 'expanded';
        }

        if (self::isHiddenFrame($trace, $projectRoot)) {
            return 'compact';
        }

        $displayCodeSnippet = $isFirstUserCode;
        if ($displayCodeSnippet) {
            $isFirstUserCode = false;
        }

        return $displayCodeSnippet ? 'expanded' : '';
    }

    public static function isPincorePath(string $file): bool
    {
        return str_contains(self::normalizePath($file), '/pincore/');
    }

    public static function isFrameworkSurfacePath(string $file, ?string $projectRoot = null): bool
    {
        return self::isPincorePath($file)
            || self::isSystemPath($file, $projectRoot)
            || self::isProjectEntryPath($file, $projectRoot);
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

