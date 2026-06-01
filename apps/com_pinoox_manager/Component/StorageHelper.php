<?php

namespace App\com_pinoox_manager\Component;

use FilesystemIterator;
use Pinoox\Component\File;
use Pinoox\Portal\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageHelper
{
    public static function defaultPath(): string
    {
        return (string) path('~');
    }

    public static function defaultLimitGb(): float
    {
        return 10.0;
    }

    public static function mode(): string
    {
        $options = Config::name('options')->get() ?? [];
        $mode = (string) ($options['storage_mode'] ?? 'auto');

        return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
    }

    public static function settings(): array
    {
        $options = Config::name('options')->get() ?? [];
        $path = trim((string) ($options['storage_path'] ?? ''));
        $limit = (float) ($options['storage_limit_gb'] ?? 0);

        if ($path === '')
            $path = self::defaultPath();

        if ($limit <= 0)
            $limit = self::defaultLimitGb();

        $resolved = self::resolvePath($path);

        return [
            'mode' => self::mode(),
            'path' => $path,
            'resolved_path' => $resolved,
            'limit_gb' => round($limit, 2),
        ];
    }

    public static function saveSettings(string $mode, string $path = '', float $limitGb = 0): array
    {
        $mode = in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';

        if ($mode === 'manual') {
            $path = trim($path);
            $limitGb = max(0.1, round($limitGb, 2));
            $resolved = self::resolvePath($path);

            if (!$resolved)
                return ['saved' => false, 'message' => 'مسیر انتخاب‌شده معتبر نیست'];

            Config::name('options')
                ->set('storage_mode', 'manual')
                ->set('storage_path', $path)
                ->set('storage_limit_gb', $limitGb)
                ->save();
        } else {
            Config::name('options')
                ->set('storage_mode', 'auto')
                ->save();
        }

        return [
            'saved' => true,
            'settings' => self::settings(),
            'stats' => self::stats(),
        ];
    }

    public static function browse(?string $path = null): array
    {
        $root = realpath(self::defaultPath());

        if (!$root)
            return [
                'root_path' => '',
                'current_path' => '',
                'parent_path' => null,
                'folders' => [],
            ];

        $rootNormalized = rtrim(str_replace('\\', '/', $root), '/');
        $current = $path ? self::resolvePath($path) : $root;

        if (!$current)
            $current = $root;

        $currentNormalized = rtrim(str_replace('\\', '/', $current), '/');
        $folders = [];

        foreach (scandir($current) ?: [] as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $fullPath = $current . DIRECTORY_SEPARATOR . $item;

            if (!is_dir($fullPath) || !is_readable($fullPath))
                continue;

            $folders[] = [
                'name' => $item,
                'path' => str_replace('\\', '/', $fullPath),
            ];
        }

        usort($folders, fn(array $a, array $b) => strcasecmp($a['name'], $b['name']));

        $parentPath = null;

        if ($currentNormalized !== $rootNormalized) {
            $parent = realpath(dirname($current));

            if ($parent) {
                $parentNormalized = rtrim(str_replace('\\', '/', $parent), '/');

                if ($parentNormalized === $rootNormalized || str_starts_with($parentNormalized, $rootNormalized . '/'))
                    $parentPath = $parentNormalized;
            }
        }

        return [
            'root_path' => $rootNormalized,
            'current_path' => $currentNormalized,
            'parent_path' => $parentPath,
            'folders' => $folders,
        ];
    }

    public static function resolvePath(string $path): ?string
    {
        if ($path === '')
            return null;

        $root = realpath(self::defaultPath());

        if (!$root)
            return null;

        $path = str_replace('\\', '/', trim($path));
        $rootNormalized = str_replace('\\', '/', $root);

        if (!str_starts_with($path, $rootNormalized) && !preg_match('#^[A-Za-z]:/#', $path))
            $path = $rootNormalized . '/' . ltrim($path, '/');

        $candidate = realpath(str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$candidate || !is_dir($candidate))
            return null;

        $candidateNormalized = rtrim(str_replace('\\', '/', $candidate), '/');

        if ($candidateNormalized !== $rootNormalized && !str_starts_with($candidateNormalized, $rootNormalized . '/'))
            return null;

        return $candidate;
    }

    public static function directorySizeBytes(string $path): int
    {
        if (!is_dir($path))
            return 0;

        $size = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile())
                    $size += $file->getSize();
            }
        } catch (\Throwable) {
            return 0;
        }

        return $size;
    }

    public static function stats(): array
    {
        if (self::mode() === 'manual')
            return self::manualStats();

        return self::autoStats();
    }

    private static function autoStats(): array
    {
        $root = self::defaultPath();
        $resolved = self::resolvePath($root) ?: realpath($root);
        $totalBytes = @disk_total_space($resolved ?: $root) ?: 0;
        $freeBytes = @disk_free_space($resolved ?: $root) ?: 0;
        $usedBytes = max(0, $totalBytes - $freeBytes);

        $totalGb = (float) File::convert_size($totalBytes, 'B', 'GB', 2);
        $usedGb = (float) File::convert_size($usedBytes, 'B', 'GB', 2);
        $freeGb = (float) File::convert_size($freeBytes, 'B', 'GB', 2);
        $percent = $totalGb > 0 ? min(100, round(($usedGb / $totalGb) * 100, 1)) : 0;

        return [
            'mode' => 'auto',
            'use' => $usedGb,
            'total' => $totalGb,
            'free' => $freeGb,
            'percent' => $percent,
            'path' => $root,
            'resolved_path' => $resolved ? str_replace('\\', '/', $resolved) : null,
            'path_valid' => (bool) $resolved,
            'used_bytes' => $usedBytes,
            'source_label' => 'دیسک سرور',
        ];
    }

    private static function manualStats(): array
    {
        $settings = self::settings();
        $resolved = $settings['resolved_path'];
        $limitGb = (float) $settings['limit_gb'];

        $usedBytes = $resolved ? self::directorySizeBytes($resolved) : 0;
        $usedGb = (float) File::convert_size($usedBytes, 'B', 'GB', 2);
        $percent = $limitGb > 0 ? min(100, round(($usedGb / $limitGb) * 100, 1)) : 0;

        return [
            'mode' => 'manual',
            'use' => $usedGb,
            'total' => $limitGb,
            'free' => max(0, round($limitGb - $usedGb, 2)),
            'percent' => $percent,
            'path' => $settings['path'],
            'resolved_path' => $resolved ? str_replace('\\', '/', $resolved) : null,
            'path_valid' => (bool) $resolved,
            'used_bytes' => $usedBytes,
            'limit_gb' => $limitGb,
            'source_label' => 'پوشه پروژه',
        ];
    }
}
