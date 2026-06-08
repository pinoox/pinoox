<?php

namespace Tests\Support;

use Pinoox\Component\Test\AppTestKit;

/**
 * Isolated filesystem paths for feature tests — never write to production app data.
 */
final class TestSandbox
{
    public static function root(): string
    {
        return AppTestKit::projectRoot() . '/tests/Fixtures/sandbox';
    }

    public static function ensure(string $relative = ''): string
    {
        $path = self::path($relative);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    public static function path(string $relative = ''): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return $relative === '' ? self::root() : self::root() . '/' . $relative;
    }

    /** Fake project document root (for serve / web-server fix checks). */
    public static function documentRoot(): string
    {
        return self::ensure('docroot');
    }

    public static function pinkerApps(): string
    {
        return self::ensure('pinker/apps');
    }

    public static function write(string $relative, string $contents): string
    {
        $file = self::path($relative);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, $contents);

        return $file;
    }

    public static function touch(string $relative, string $contents = ''): string
    {
        return self::write($relative, $contents);
    }

    /**
     * Test-only package name — always com_test_* so cleanupTransientArtifacts removes it.
     */
    public static function packageName(string $suffix): string
    {
        $suffix = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $suffix) ?? 'app');
        $suffix = trim($suffix, '_');

        return 'com_test_' . ($suffix === '' ? 'app' : $suffix);
    }

    public static function fakeAppPath(string $suffix, string $subPath = ''): string
    {
        return AppTestKit::path(self::packageName($suffix), $subPath);
    }

    public static function cleanup(): void
    {
        AppTestKit::cleanupTransientArtifacts();
    }
}
