<?php

namespace Pinoox\Component\Server;

final class WebServerFixRegistry
{
    /** @var array<string, array<string, array{relative: string, name: string, full?: string|null}>> */
    private static array $pending = [];

    public static function register(
        string $package,
        string $relativePath,
        string $routeName,
        ?string $fullPath = null,
    ): void {
        $relative = WebServerFix::normalizePath($relativePath);

        self::$pending[$package][$relative] = [
            'relative' => $relative,
            'name' => $routeName,
            'full' => $fullPath !== null ? WebServerFix::normalizePath($fullPath) : null,
        ];
    }

    public static function flush(string $package): void
    {
        if (empty(self::$pending[$package])) {
            return;
        }

        WebServerFixCache::merge($package, array_values(self::$pending[$package]));
        unset(self::$pending[$package]);
    }
}
