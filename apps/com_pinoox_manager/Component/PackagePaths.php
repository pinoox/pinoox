<?php

/**
 * Staged .pinx packages under project storage (~storage/packages/).
 */
namespace App\com_pinoox_manager\Component;

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Portal\File as FilePortal;

final class PackagePaths
{
    public const MANUAL = 'packages/manual';

    public const APPS = 'packages/apps';

    public const TEMPLATES = 'packages/templates';

    private const LEGACY_MANUAL = '~/downloads/packages/manual/';

    private const LEGACY_APPS = '~/downloads/apps/';

    private const LEGACY_TEMPLATES = '~/downloads/templates/';

    private const LEGACY_STORAGE_MANUAL = 'downloads/packages/manual';

    private const APP_SCOPED_PREFIX = 'manager/packages';

    public static function storage(): FilesystemAdapter
    {
        return ManagerStorage::disk();
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
        self::migrateLegacySources(self::MANUAL);

        return self::storage()->path(self::MANUAL);
    }

    public static function ensureAppsDir(): string
    {
        self::ensureDir(self::APPS);
        self::migrateLegacySources(self::APPS);

        return self::storage()->path(self::APPS);
    }

    public static function ensureTemplatesDir(): string
    {
        self::ensureDir(self::TEMPLATES);
        self::migrateLegacySources(self::TEMPLATES);

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
        ManagerStorage::ensureDir($key);
    }

    private static function migrateLegacySources(string $targetKey): void
    {
        $legacyMap = [
            self::MANUAL => [
                path(self::LEGACY_MANUAL),
                path('~storage/' . self::LEGACY_STORAGE_MANUAL),
            ],
            self::APPS => [
                path(self::LEGACY_APPS),
            ],
            self::TEMPLATES => [
                path(self::LEGACY_TEMPLATES),
            ],
        ];

        foreach ($legacyMap[$targetKey] ?? [] as $legacyDir) {
            ManagerStorage::migrateFromDir($legacyDir, $targetKey);
        }

        $appDisk = FilePortal::storage();
        ManagerStorage::migrateFromDisk($appDisk, self::APP_SCOPED_PREFIX . '/' . basename($targetKey), $targetKey);
    }
}
