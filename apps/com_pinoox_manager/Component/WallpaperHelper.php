<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_manager\Component;

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Portal\File;
use Pinoox\Portal\Url;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WallpaperHelper
{
    private const FOLDER = 'wallpapers';

    private const LEGACY_APP_FOLDER = 'system/wallpapers';

    private const APP_SCOPED_FOLDERS = [
        'manager/wallpapers',
        'system/wallpapers',
    ];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const PREFERRED_DEFAULT = 'default';

    private const MAX_SIZE = '5MB';

    public static function all(): array
    {
        $files = [];

        foreach (self::listLegacyFiles() as $item) {
            $files[$item['id']] = $item;
        }

        foreach (self::listStorageFiles() as $item) {
            $files[$item['id']] = $item;
        }

        ksort($files, SORT_NATURAL);

        return array_values($files);
    }

    private static function maxUploadBytes(): int
    {
        return 5 * 1024 * 1024;
    }

    public static function defaultId(): string
    {
        $wallpapers = self::all();
        if ($wallpapers === []) {
            return '';
        }

        foreach ($wallpapers as $wallpaper) {
            if ($wallpaper['id'] === self::PREFERRED_DEFAULT) {
                return $wallpaper['id'];
            }
        }

        return $wallpapers[0]['id'];
    }

    public static function resolve(?string $selected): string
    {
        $wallpapers = self::all();
        if ($wallpapers === []) {
            return '';
        }

        $selected = trim(basename((string) $selected));
        $selected = pathinfo($selected, PATHINFO_FILENAME);

        foreach ($wallpapers as $wallpaper) {
            if ($wallpaper['id'] === $selected) {
                return $selected;
            }
        }

        return self::defaultId();
    }

    public static function filePath(string $name): ?string
    {
        $id = self::normalizeId($name);

        $legacy = self::findLegacyPath($id);
        if ($legacy !== null) {
            return $legacy;
        }

        $storageKey = self::findStorageKey($id);
        if ($storageKey === null || !self::storage()->exists($storageKey)) {
            return null;
        }

        try {
            return self::storage()->path($storageKey);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function contents(string $name): ?string
    {
        $id = self::normalizeId($name);
        $storageKey = self::findStorageKey($id);

        if ($storageKey !== null && self::storage()->exists($storageKey)) {
            return self::storage()->get($storageKey);
        }

        $legacy = self::findLegacyPath($id);
        if ($legacy !== null && is_file($legacy)) {
            $content = file_get_contents($legacy);

            return $content === false ? null : $content;
        }

        return null;
    }

    public static function exists(string $name): bool
    {
        $id = self::normalizeId($name);

        return self::findStorageKey($id) !== null || self::findLegacyPath($id) !== null;
    }

    public static function upload(UploadedFile $file): ?array
    {
        self::migrateLegacyStorageFolder();

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::EXTENSIONS, true)) {
            return null;
        }

        if ($file->getSize() > self::maxUploadBytes()) {
            return null;
        }

        $disk = self::storage();
        ManagerStorage::ensureDir(self::FOLDER);

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $base) ?: 'wallpaper';
        $base = trim($base, '-');
        $filename = $base . '.' . $ext;
        $counter = 1;

        while ($disk->exists(self::FOLDER . '/' . $filename)) {
            $filename = $base . '-' . $counter . '.' . $ext;
            $counter++;
        }

        $stored = $disk->putFileAs(self::FOLDER, $file, $filename);
        if ($stored === false) {
            return null;
        }

        return self::itemFromStorageKey(self::FOLDER . '/' . $filename);
    }

    public static function delete(string $name): bool
    {
        $id = self::normalizeId($name);
        $deleted = false;

        $storageKey = self::findStorageKey($id);
        if ($storageKey !== null && self::storage()->exists($storageKey)) {
            $deleted = self::storage()->delete($storageKey) || $deleted;
        }

        $legacy = self::findLegacyPath($id);
        if ($legacy !== null && is_file($legacy)) {
            $deleted = @unlink($legacy) || $deleted;
        }

        return $deleted;
    }

    public static function url(string $fileName): string
    {
        return Url::to('api/v1/wallpapers/' . rawurlencode(self::normalizeId($fileName)));
    }

    public static function mimeType(string $name): string
    {
        $ext = strtolower(pathinfo(basename($name), PATHINFO_EXTENSION));

        if ($ext === '') {
            $id = self::normalizeId($name);
            $storageKey = self::findStorageKey($id);

            if ($storageKey !== null) {
                $ext = strtolower(pathinfo($storageKey, PATHINFO_EXTENSION));
            } elseif (($legacy = self::findLegacyPath($id)) !== null) {
                $ext = strtolower(pathinfo($legacy, PATHINFO_EXTENSION));
            }
        }

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    private static function storage(): FilesystemAdapter
    {
        return ManagerStorage::disk();
    }

    /**
     * @return list<array{id: string, file: string, url: string, deletable: bool}>
     */
    private static function listStorageFiles(): array
    {
        self::migrateLegacyStorageFolder();

        $disk = self::storage();

        if (!$disk->exists(self::FOLDER)) {
            return [];
        }

        $items = [];

        foreach ($disk->files(self::FOLDER) as $key) {
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if (!in_array($ext, self::EXTENSIONS, true)) {
                continue;
            }

            $items[] = self::itemFromStorageKey($key);
        }

        return $items;
    }

    /**
     * @return list<array{id: string, file: string, url: string, deletable: bool}>
     */
    private static function listLegacyFiles(): array
    {
        $dir = self::legacyFolder();
        $items = [];

        foreach (self::EXTENSIONS as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $items[] = self::itemFromLegacyFile($file);
            }
        }

        return $items;
    }

    private static function legacyFolder(): string
    {
        $dir = path(self::LEGACY_APP_FOLDER);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private static function migrateLegacyStorageFolder(): void
    {
        $appDisk = File::storage();

        foreach (self::APP_SCOPED_FOLDERS as $folder) {
            ManagerStorage::migrateFromDisk($appDisk, $folder, self::FOLDER);
        }
    }

    private static function normalizeId(string $name): string
    {
        return pathinfo(basename($name), PATHINFO_FILENAME);
    }

    private static function findStorageKey(string $id): ?string
    {
        $disk = self::storage();

        foreach (self::EXTENSIONS as $ext) {
            $key = self::FOLDER . '/' . $id . '.' . $ext;
            if ($disk->exists($key)) {
                return $key;
            }
        }

        if (!$disk->exists(self::FOLDER)) {
            return null;
        }

        foreach ($disk->files(self::FOLDER) as $key) {
            if (self::normalizeId($key) === $id) {
                return $key;
            }
        }

        return null;
    }

    private static function findLegacyPath(string $id): ?string
    {
        return self::findInFolder(self::legacyFolder(), $id);
    }

    /**
     * @return array{id: string, file: string, url: string, deletable: bool}
     */
    private static function itemFromStorageKey(string $key): array
    {
        $file = basename($key);

        return [
            'id' => self::normalizeId($file),
            'file' => $file,
            'url' => self::url($file),
            'deletable' => true,
        ];
    }

    /**
     * @return array{id: string, file: string, url: string, deletable: bool}
     */
    private static function itemFromLegacyFile(string $file): array
    {
        $id = pathinfo($file, PATHINFO_FILENAME);

        return [
            'id' => $id,
            'file' => basename($file),
            'url' => self::url(basename($file)),
            'deletable' => true,
        ];
    }

    private static function findInFolder(string $dir, string $id): ?string
    {
        if (!is_dir($dir)) {
            return null;
        }

        foreach (self::EXTENSIONS as $ext) {
            $file = $dir . '/' . $id . '.' . $ext;
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}
