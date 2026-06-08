<?php

namespace Pinoox\Component\File;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Visibility;
use Pinoox\Portal\Storage;
use Pinoox\Portal\Url;
use Pinoox\Model\FileModel;

class FileStorage
{
    public static function disk(?string $package = null, ?string $disk = null): FilesystemAdapter
    {
        $config = FileConfig::resolve();
        $package = $package ?? $config['package'];
        $disk = $disk ?? $config['disk'];

        return Storage::app($package, $disk);
    }

    public static function key(string $directory, string $filename): string
    {
        return trim(trim($directory, '/') . '/' . ltrim($filename, '/'), '/');
    }

    public static function thumbKey(string $directory, string $filename): string
    {
        return self::key(trim($directory, '/') . '/thumbs', 'thumb_' . ltrim($filename, '/'));
    }

    public static function visibility(string $access): string
    {
        return strtolower($access) === 'public' ? Visibility::PUBLIC : Visibility::PRIVATE;
    }

    public static function resolveDisk(FileModel $file): ?string
    {
        $metadata = $file->file_metadata ?? [];

        return is_array($metadata) && !empty($metadata['disk'])
            ? (string) $metadata['disk']
            : null;
    }

    public static function url(FileModel $file): ?string
    {
        if (empty($file->file_name) || empty($file->file_path)) {
            return null;
        }

        $disk = self::disk($file->app, self::resolveDisk($file));
        $key = self::key($file->file_path, $file->file_name);
        $url = self::tryDiskUrl($disk, $key);

        if ($url !== null) {
            return $url;
        }

        if (self::legacyExists($file)) {
            return Url::asset($file->file_path . '/' . $file->file_name);
        }

        return Url::asset($file->file_path . '/' . $file->file_name);
    }

    public static function thumbUrl(FileModel $file): ?string
    {
        if ($file->file_ext === 'svg') {
            return self::url($file);
        }

        if (!in_array($file->file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        $disk = self::disk($file->app, self::resolveDisk($file));
        $key = self::thumbKey($file->file_path, $file->file_name);
        $url = self::tryDiskUrl($disk, $key);

        if ($url !== null) {
            return $url;
        }

        if (self::legacyThumbExists($file)) {
            return Url::asset($file->file_path . '/thumbs/thumb_' . $file->file_name);
        }

        return null;
    }

    public static function delete(FileModel $file): void
    {
        $disk = self::disk($file->app, self::resolveDisk($file));
        $paths = [
            self::key($file->file_path, $file->file_name),
            self::thumbKey($file->file_path, $file->file_name),
        ];

        $existing = array_values(array_filter($paths, static fn (string $path) => $disk->exists($path)));

        if ($existing !== []) {
            $disk->delete($existing);
        }

        self::deleteLegacy($file);
    }

    private static function tryDiskUrl(FilesystemAdapter $disk, string $key): ?string
    {
        if (!$disk->exists($key)) {
            return null;
        }

        try {
            $url = $disk->url($key);

            return is_string($url) && $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function legacyExists(FileModel $file): bool
    {
        $path = path($file->file_path, $file->app) . '/' . $file->file_name;

        return is_file($path);
    }

    private static function legacyThumbExists(FileModel $file): bool
    {
        $path = path($file->file_path, $file->app) . '/thumbs/thumb_' . $file->file_name;

        return is_file($path);
    }

    private static function deleteLegacy(FileModel $file): void
    {
        $base = path($file->file_path, $file->app);
        $original = $base . '/' . $file->file_name;
        $thumb = $base . '/thumbs/thumb_' . $file->file_name;

        if (is_file($original)) {
            unlink($original);
        }

        if (is_file($thumb)) {
            unlink($thumb);
        }
    }
}

