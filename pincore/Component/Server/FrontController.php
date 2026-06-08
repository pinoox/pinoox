<?php

namespace Pinoox\Component\Server;

/**
 * @deprecated Use {@see WebServerFix} directly. Kept for server.php and legacy references.
 */
final class FrontController
{
    public const PINOOX_JS = '/dist/pinoox.js';

    /**
     * @return list<string>
     */
    public static function paths(): array
    {
        return WebServerFixCache::allRelativePaths();
    }

    public static function isPinooxJsPath(string $path): bool
    {
        return WebServerFix::pathHasStaticExtension($path);
    }

    public static function isRootPinooxJsPath(string $path): bool
    {
        return WebServerFix::isRootFixPath($path);
    }

    public static function matches(string $uri, ?string $documentRoot = null): bool
    {
        return WebServerFix::shouldRoute($uri, $documentRoot);
    }

    public static function shouldRoute(string $uri, ?string $documentRoot = null): bool
    {
        return WebServerFix::shouldRoute($uri, $documentRoot);
    }

    public static function applyServerGlobals(string $uri): void
    {
        WebServerFix::applyServerGlobals($uri);
    }
}
