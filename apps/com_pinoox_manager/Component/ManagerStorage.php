<?php

namespace App\com_pinoox_manager\Component;

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Portal\Storage;

/**
 * Project storage root (~storage) for manager uploads.
 */
final class ManagerStorage
{
    private static ?FilesystemAdapter $disk = null;

    public static function root(): string
    {
        return path('~storage');
    }

    public static function disk(): FilesystemAdapter
    {
        return self::$disk ??= Storage::build(self::root());
    }

    public static function path(string $key): string
    {
        return self::disk()->path($key);
    }

    public static function ensureDir(string $key): string
    {
        $disk = self::disk();
        if (!$disk->exists($key)) {
            $disk->makeDirectory($key);
        }

        return $disk->path($key);
    }

    public static function migrateFromDir(string $sourceDir, string $targetKey): void
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        self::ensureDir($targetKey);
        $disk = self::disk();

        foreach (scandir($sourceDir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = $sourceDir . DIRECTORY_SEPARATOR . $item;
            if (!is_file($source)) {
                continue;
            }

            $targetKeyPath = $targetKey . '/' . $item;
            if ($disk->exists($targetKeyPath)) {
                @unlink($source);
                continue;
            }

            $targetPath = $disk->path($targetKeyPath);
            if (@rename($source, $targetPath)) {
                continue;
            }

            if (@copy($source, $targetPath)) {
                @unlink($source);
            }
        }

        @rmdir($sourceDir);
    }

    public static function migrateFromDisk(FilesystemAdapter $sourceDisk, string $sourceKey, string $targetKey): void
    {
        if (!$sourceDisk->exists($sourceKey)) {
            return;
        }

        self::ensureDir($targetKey);
        $disk = self::disk();

        foreach ($sourceDisk->files($sourceKey) as $fileKey) {
            $basename = basename($fileKey);
            $target = $targetKey . '/' . $basename;

            if ($disk->exists($target)) {
                $sourceDisk->delete($fileKey);
                continue;
            }

            $contents = $sourceDisk->get($fileKey);
            if ($contents !== null && $disk->put($target, $contents)) {
                $sourceDisk->delete($fileKey);
            }
        }

        if ($sourceDisk->exists($sourceKey)) {
            $sourceDisk->deleteDirectory($sourceKey);
        }
    }
}
