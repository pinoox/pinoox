<?php

/**
 * Staged .pinx packages under the manager app storage disk (~storage/apps/com_pinoox_manager/).
 */
namespace App\com_pinoox_manager\Component;

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Portal\File as FilePortal;

final class PackagePaths
{
    public const MANUAL = 'manager/packages/manual';

    public const APPS = 'manager/packages/apps';

    public const TEMPLATES = 'manager/packages/templates';

    private const LEGACY_MANUAL = '~/downloads/packages/manual/';

    private const LEGACY_APPS = '~/downloads/apps/';

    private const LEGACY_TEMPLATES = '~/downloads/templates/';

    public static function storage(): FilesystemAdapter
    {
        return FilePortal::storage();
    }

    public static function manualDir(): string
    {
        return self::ensureManualDir();
    }

    public static function manualFile(string $filename): string
    {
        $filename = basename($filename);
        $key = self::MANUAL . '/' . $filename;

        if (self::storage()->exists($key)) {
            return self::storage()->path($key);
        }

        $legacy = path(self::LEGACY_MANUAL . $filename);
        if (is_file($legacy)) {
            return $legacy;
        }

        return self::storage()->path($key);
    }

    public static function appsFile(string $packageName): string
    {
        $key = self::appsKey($packageName);

        if (self::storage()->exists($key)) {
            return self::storage()->path($key);
        }

        $legacy = path(self::LEGACY_APPS . basename($packageName) . '.pinx');
        if (is_file($legacy)) {
            return $legacy;
        }

        self::ensureDir(self::APPS);

        return self::storage()->path($key);
    }

    public static function templatesFile(string $uid): string
    {
        $key = self::templatesKey($uid);

        if (self::storage()->exists($key)) {
            return self::storage()->path($key);
        }

        $legacy = path(self::LEGACY_TEMPLATES . basename($uid) . '.pinx');
        if (is_file($legacy)) {
            return $legacy;
        }

        self::ensureDir(self::TEMPLATES);

        return self::storage()->path($key);
    }

    public static function appsKey(string $packageName): string
    {
        return self::APPS . '/' . basename($packageName) . '.pinx';
    }

    public static function templatesKey(string $uid): string
    {
        return self::TEMPLATES . '/' . basename($uid) . '.pinx';
    }

    public static function ensureManualDir(): string
    {
        self::ensureDir(self::MANUAL);
        self::migrateLegacyDir(self::LEGACY_MANUAL, self::MANUAL);

        return self::storage()->path(self::MANUAL);
    }

    public static function ensureAppsDir(): string
    {
        self::ensureDir(self::APPS);
        self::migrateLegacyDir(self::LEGACY_APPS, self::APPS);

        return self::storage()->path(self::APPS);
    }

    public static function ensureTemplatesDir(): string
    {
        self::ensureDir(self::TEMPLATES);
        self::migrateLegacyDir(self::LEGACY_TEMPLATES, self::TEMPLATES);

        return self::storage()->path(self::TEMPLATES);
    }

    /**
     * @return list<string>
     */
    public static function listManualFiles(): array
    {
        self::ensureManualDir();

        $files = [];
        $disk = self::storage();

        foreach ($disk->files(self::MANUAL) as $key) {
            if (strtolower(pathinfo($key, PATHINFO_EXTENSION)) !== 'pinx') {
                continue;
            }

            $files[] = $disk->path($key);
        }

        $legacyDir = path(self::LEGACY_MANUAL);
        if (is_dir($legacyDir)) {
            foreach (glob($legacyDir . '/*.pinx') ?: [] as $file) {
                if (is_file($file)) {
                    $files[] = $file;
                }
            }
        }

        return array_values(array_unique($files));
    }

    private static function ensureDir(string $key): void
    {
        $disk = self::storage();
        if (!$disk->exists($key)) {
            $disk->makeDirectory($key);
        }
    }

    private static function migrateLegacyDir(string $legacyPath, string $storageKey): void
    {
        $legacyDir = path($legacyPath);
        if (!is_dir($legacyDir)) {
            return;
        }

        $disk = self::storage();
        self::ensureDir($storageKey);

        foreach (scandir($legacyDir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $legacyDir . DIRECTORY_SEPARATOR . $item;
            if (!is_file($source)) {
                continue;
            }

            $targetKey = $storageKey . '/' . $item;
            if ($disk->exists($targetKey)) {
                @unlink($source);
                continue;
            }

            $targetPath = $disk->path($targetKey);
            if (@rename($source, $targetPath)) {
                continue;
            }

            if (@copy($source, $targetPath)) {
                @unlink($source);
            }
        }

        @rmdir($legacyDir);
    }
}
