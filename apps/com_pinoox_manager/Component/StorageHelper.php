<?php

namespace App\com_pinoox_manager\Component;

use FilesystemIterator;
use Pinoox\Component\File;
use Pinoox\Model\FileModel;
use Pinoox\Portal\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageHelper
{
    private const SIZE_SCAN_VERSION = 4;

    private const DATABASE_CACHE_KEY = '@database';

    public static function defaultPath(): string
    {
        return (string) path('~');
    }

    public static function defaultLimitGb(): float
    {
        return 10.0;
    }

    public static function defaultMode(): string
    {
        return 'auto';
    }

    public static function mode(): string
    {
        $options = Config::name('options')->get() ?? [];

        return self::normalizeMode((string) ($options['storage_mode'] ?? self::defaultMode()));
    }

    private static function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if ($mode === 'manual')
            return 'directory';

        return in_array($mode, ['auto', 'directory', 'database'], true) ? $mode : self::defaultMode();
    }

    public static function isDatabaseSourceAvailable(): bool
    {
        try {
            FileModel::withoutGlobalScopes()->limit(1)->get();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function settings(): array
    {
        $options = Config::name('options')->get() ?? [];
        $mode = self::mode();
        $savedPath = trim((string) ($options['storage_path'] ?? ''));
        $savedLimit = (float) ($options['storage_limit_gb'] ?? 0);
        $root = self::rootPath();

        $base = [
            'mode' => $mode,
            'default_path' => self::defaultPath(),
            'root_path' => $root,
            'project_root_path' => $root,
            'database_available' => self::isDatabaseSourceAvailable(),
        ];

        if ($mode === 'directory') {
            $resolved = $savedPath !== '' ? self::resolveDirectoryPath($savedPath) : null;

            return array_merge($base, [
                'path' => $savedPath,
                'resolved_path' => $resolved ? self::normalizePath($resolved) : null,
                'limit_gb' => $savedLimit > 0 ? round($savedLimit, 2) : self::defaultLimitGb(),
                'path_valid' => self::isReadableDirectory($resolved),
            ]);
        }

        if ($mode === 'database') {
            return array_merge($base, [
                'path' => '',
                'resolved_path' => null,
                'limit_gb' => $savedLimit > 0 ? round($savedLimit, 2) : self::defaultLimitGb(),
                'path_valid' => self::isDatabaseSourceAvailable(),
            ]);
        }

        return array_merge($base, [
            'path' => '',
            'resolved_path' => null,
            'limit_gb' => self::defaultLimitGb(),
            'saved_path' => $savedPath,
            'saved_limit_gb' => $savedLimit > 0 ? round($savedLimit, 2) : self::defaultLimitGb(),
            'path_valid' => true,
        ]);
    }

    public static function saveSettings(string $mode, string $path = '', float $limitGb = 0): array
    {
        $mode = self::normalizeMode($mode);

        if ($mode === 'directory') {
            $path = trim($path);
            $limitGb = round($limitGb, 2);
            $resolved = self::resolveDirectoryPath($path);

            if ($path === '' || !$resolved)
                return ['saved' => false, 'message' => 'مسیر انتخاب‌شده معتبر نیست یا قابل خواندن نیست'];

            if (!self::isReadableDirectory($resolved))
                return ['saved' => false, 'message' => 'دسترسی خواندن این پوشه وجود ندارد'];

            if ($limitGb <= 0)
                return ['saved' => false, 'message' => 'حجم کل باید بزرگ‌تر از صفر باشد'];

            @set_time_limit(120);

            Config::name('options')
                ->set('storage_mode', 'directory')
                ->set('storage_path', self::normalizePath($resolved))
                ->set('storage_limit_gb', $limitGb)
                ->save();

            self::refreshDirectoryUsedBytes(self::normalizePath($resolved), true);
        } elseif ($mode === 'database') {
            $limitGb = round($limitGb, 2);

            if ($limitGb <= 0)
                return ['saved' => false, 'message' => 'حجم کل باید بزرگ‌تر از صفر باشد'];

            if (!self::isDatabaseSourceAvailable())
                return ['saved' => false, 'message' => 'جدول فایل‌ها در دیتابیس در دسترس نیست'];

            Config::name('options')
                ->set('storage_mode', 'database')
                ->set('storage_limit_gb', $limitGb)
                ->save();

            self::refreshDatabaseUsedBytes(true);
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
        $projectRoot = self::rootRealPath();

        if (!$projectRoot) {
            return [
                'root_path' => '',
                'project_root_path' => '',
                'current_path' => '',
                'parent_path' => null,
                'can_select_current' => false,
                'folders' => [],
            ];
        }

        $projectRootNormalized = self::normalizePath($projectRoot);
        $current = $projectRoot;

        if ($path !== null && trim($path) !== '') {
            $resolved = self::resolveDirectoryPath($path);
            $current = $resolved ?: $projectRoot;
        }

        $currentNormalized = self::normalizePath($current);
        $folders = [];

        foreach (scandir($current) ?: [] as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $fullPath = $current . DIRECTORY_SEPARATOR . $item;

            if (!is_dir($fullPath) || !is_readable($fullPath))
                continue;

            $allowed = realpath($fullPath);

            if (!$allowed || !is_dir($allowed) || !is_readable($allowed))
                continue;

            $folders[] = [
                'name' => $item,
                'path' => self::normalizePath($allowed),
            ];
        }

        usort($folders, fn(array $a, array $b) => strcasecmp($a['name'], $b['name']));

        $parentPath = null;
        $parent = realpath(dirname($current));

        if ($parent && $parent !== $current && is_dir($parent) && is_readable($parent))
            $parentPath = self::normalizePath($parent);

        return [
            'root_path' => $projectRootNormalized,
            'project_root_path' => $projectRootNormalized,
            'current_path' => $currentNormalized,
            'parent_path' => $parentPath,
            'can_select_current' => self::isReadableDirectory($current),
            'folders' => $folders,
        ];
    }

    public static function resolveDirectoryPath(string $path): ?string
    {
        if ($path === '')
            return null;

        $path = str_replace('\\', '/', trim($path));
        $projectRoot = self::rootRealPath();
        $projectRootNormalized = $projectRoot ? self::normalizePath($projectRoot) : '';

        if ($projectRootNormalized !== ''
            && !str_starts_with($path, $projectRootNormalized)
            && !preg_match('#^[A-Za-z]:/#', $path))
            $path = $projectRootNormalized . '/' . ltrim($path, '/');

        $candidate = realpath(str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$candidate || !is_dir($candidate) || !is_readable($candidate))
            return null;

        return $candidate;
    }

    /** @deprecated Use resolveDirectoryPath() */
    public static function resolvePath(string $path): ?string
    {
        return self::resolveDirectoryPath($path);
    }

    /**
     * @return array{bytes: int, complete: bool}
     */
    public static function directorySizeBytes(string $path, int $maxSeconds = 60, bool $fastOnly = false): array
    {
        $resolved = realpath(str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$resolved || !is_dir($resolved) || !is_readable($resolved))
            return ['bytes' => 0, 'complete' => false];

        $fast = self::tryFastDirectorySize($resolved, $maxSeconds);

        if ($fast !== null)
            return $fast;

        if ($fastOnly)
            return ['bytes' => 0, 'complete' => false];

        return self::directorySizeBytesRecursive($resolved, $maxSeconds);
    }

    /**
     * @return array{bytes: int, complete: bool}|null
     */
    private static function tryFastDirectorySize(string $path, int $maxSeconds): ?array
    {
        if (!self::isShellAvailable())
            return null;

        if (PHP_OS_FAMILY === 'Windows')
            return self::directorySizeViaWindowsDir($path, $maxSeconds);

        return self::directorySizeViaDu($path, $maxSeconds);
    }

    /**
     * @return array{bytes: int, complete: bool}|null
     */
    private static function directorySizeViaWindowsDir(string $path, int $maxSeconds): ?array
    {
        $output = self::runShell(
            'cmd /c dir /s /-c ' . escapeshellarg($path) . ' 2>nul | findstr /i /c:"File(s)"',
            $maxSeconds
        );

        if ($output === null || $output === '') {
            $output = self::directorySizeViaWindowsPowerShell($path, $maxSeconds);
        }

        if ($output === null || $output === '')
            return null;

        if (preg_match('/^\d+$/', trim($output))) {
            return ['bytes' => max(0, (int) trim($output)), 'complete' => true];
        }

        if (!preg_match_all(
            '/\d[\d,\.\s]*\s+(?:File\(s\)|فایل\(ها\)|فایل)\s+([\d,\.]+)\s+bytes/iu',
            $output,
            $matches
        ))
            return null;

        $bytes = (int) str_replace([',', '.'], '', (string) end($matches[1]));

        return ['bytes' => max(0, $bytes), 'complete' => true];
    }

    private static function directorySizeViaWindowsPowerShell(string $path, int $maxSeconds): ?string
    {
        $escaped = str_replace("'", "''", $path);
        $command = 'powershell -NoProfile -NonInteractive -Command '
            . '"$s=(Get-ChildItem -LiteralPath \'' . $escaped . '\' -Recurse -File -Force -ErrorAction SilentlyContinue '
            . '| Measure-Object -Property Length -Sum).Sum; if($null -ne $s){Write-Output $s}"';

        return self::runShell($command, $maxSeconds);
    }

    /**
     * @return array{bytes: int, complete: bool}|null
     */
    private static function directorySizeViaDu(string $path, int $maxSeconds): ?array
    {
        $stderr = PHP_OS_FAMILY === 'Windows' ? ' 2>nul' : ' 2>/dev/null';
        $arg = escapeshellarg($path);

        $attempts = [
            ['gdu', '-sb', 1],
            ['du', '-sb', 1],
            ['du', '-sk', 1024],
        ];

        foreach ($attempts as [$name, $flags, $multiplier]) {
            $binary = self::findExecutable($name);

            if (!$binary)
                continue;

            $output = self::runShell(trim($binary . ' ' . $flags . ' ' . $arg . $stderr), $maxSeconds);
            $bytes = self::parseDuOutputBytes($output, $multiplier);

            if ($bytes !== null)
                return ['bytes' => $bytes, 'complete' => true];
        }

        return null;
    }

    private static function parseDuOutputBytes(?string $output, int $multiplier = 1): ?int
    {
        if ($output === null || trim($output) === '')
            return null;

        if (!preg_match('/^(\d+)/', trim($output), $match))
            return null;

        return max(0, (int) $match[1] * $multiplier);
    }

    /**
     * @return array{bytes: int, complete: bool}
     */
    private static function directorySizeBytesRecursive(string $path, int $maxSeconds): array
    {
        $size = 0;
        $startedAt = microtime(true);

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($maxSeconds > 0 && (microtime(true) - $startedAt) >= $maxSeconds)
                    return ['bytes' => $size, 'complete' => false];

                if ($file->isFile())
                    $size += (int) $file->getSize();
            }
        } catch (\Throwable) {
            return ['bytes' => $size, 'complete' => false];
        }

        return ['bytes' => $size, 'complete' => true];
    }

    private static function isShellAvailable(): bool
    {
        if (!function_exists('proc_open'))
            return false;

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        foreach (['proc_open', 'proc_close', 'proc_get_status'] as $fn) {
            if ($disabled !== [''] && in_array($fn, $disabled, true))
                return false;
        }

        return true;
    }

    private static function findExecutable(string $name): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = self::runShell('where ' . escapeshellarg($name) . ' 2>nul', 5);

            if ($output === null)
                return null;

            $line = trim(strtok($output, "\r\n"));

            return $line !== '' ? $line : null;
        }

        $dirs = array_unique(array_filter(array_merge(
            explode(PATH_SEPARATOR, (string) getenv('PATH')),
            ['/usr/local/bin', '/opt/homebrew/bin', '/usr/bin', '/bin']
        )));

        foreach ($dirs as $dir) {
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

            if (is_file($candidate) && is_executable($candidate))
                return $candidate;
        }

        return null;
    }

    private static function runShell(string $command, int $maxSeconds): ?string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes);

        if (!is_resource($process))
            return null;

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        fclose($pipes[0]);

        $stdout = '';
        $stderr = '';
        $startedAt = microtime(true);

        while (true) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);

            $status = proc_get_status($process);

            if (!$status['running'])
                break;

            if ($maxSeconds > 0 && (microtime(true) - $startedAt) >= $maxSeconds) {
                proc_terminate($process);
                break;
            }

            usleep(50000);
        }

        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $output = trim($stdout !== '' ? $stdout : $stderr);

        return $output !== '' ? $output : null;
    }

    public static function stats(bool $forceRefresh = false, bool $cacheOnly = false): array
    {
        return match (self::mode()) {
            'directory' => self::directoryStats($forceRefresh, $cacheOnly),
            'database' => self::databaseStats($forceRefresh, $cacheOnly),
            default => self::autoStats(),
        };
    }

    private static function autoStats(): array
    {
        $root = self::defaultPath();
        $resolved = self::rootRealPath() ?: self::resolveDirectoryPath($root);
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
            'resolved_path' => $resolved ? self::normalizePath($resolved) : null,
            'path_valid' => (bool) $resolved,
            'used_bytes' => $usedBytes,
            'source_label' => 'دیسک سرور',
        ];
    }

    private static function directoryStats(bool $forceRefresh = false, bool $cacheOnly = false): array
    {
        $options = Config::name('options')->get() ?? [];
        $savedPath = trim((string) ($options['storage_path'] ?? ''));
        $limitGb = (float) ($options['storage_limit_gb'] ?? 0);
        $resolved = $savedPath !== '' ? self::resolveDirectoryPath($savedPath) : null;
        $pathValid = self::isReadableDirectory($resolved);
        $normalizedPath = $resolved ? self::normalizePath($resolved) : '';
        $usedInfo = $pathValid
            ? self::resolveDirectoryUsedBytes($normalizedPath, $options, $forceRefresh, $cacheOnly)
            : ['bytes' => 0, 'cached' => false, 'complete' => true, 'pending' => false];

        return self::buildManualStatsResult($usedInfo, $limitGb, [
            'mode' => 'directory',
            'path' => $savedPath,
            'resolved_path' => $normalizedPath !== '' ? $normalizedPath : null,
            'path_valid' => $pathValid,
            'source_label' => 'پوشه انتخاب‌شده',
        ]);
    }

    private static function databaseStats(bool $forceRefresh = false, bool $cacheOnly = false): array
    {
        $options = Config::name('options')->get() ?? [];
        $limitGb = (float) ($options['storage_limit_gb'] ?? 0);
        $available = self::isDatabaseSourceAvailable();
        $usedInfo = $available
            ? self::resolveDatabaseUsedBytes($options, $forceRefresh, $cacheOnly)
            : ['bytes' => 0, 'cached' => false, 'complete' => false, 'pending' => false];

        return self::buildManualStatsResult($usedInfo, $limitGb, [
            'mode' => 'database',
            'path' => '',
            'resolved_path' => null,
            'path_valid' => $available,
            'source_label' => 'جدول فایل‌های دیتابیس',
        ]);
    }

    private static function buildManualStatsResult(array $usedInfo, float $limitGb, array $meta): array
    {
        $usedBytes = (int) ($usedInfo['bytes'] ?? 0);
        $usedGb = (float) File::convert_size($usedBytes, 'B', 'GB', 2);
        $totalGb = $limitGb > 0 ? round($limitGb, 2) : self::defaultLimitGb();
        $percent = $totalGb > 0 ? min(100, round(($usedGb / $totalGb) * 100, 1)) : 0;

        return array_merge($meta, [
            'use' => $usedGb,
            'total' => $totalGb,
            'free' => max(0, round($totalGb - $usedGb, 2)),
            'percent' => $percent,
            'used_bytes' => $usedBytes,
            'limit_gb' => $totalGb,
            'size_cached' => !empty($usedInfo['cached']),
            'size_complete' => !isset($usedInfo['complete']) || $usedInfo['complete'] !== false,
            'size_pending' => !empty($usedInfo['pending']),
        ]);
    }

    private static function databaseUsedBytes(): int
    {
        try {
            return (int) FileModel::withoutGlobalScopes()->sum('file_size');
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function resolveDatabaseUsedBytes(
        array $options,
        bool $forceRefresh = false,
        bool $cacheOnly = false
    ): array {
        $cacheKey = self::DATABASE_CACHE_KEY;
        $cachedPath = (string) ($options['storage_size_path'] ?? '');
        $cachedBytes = (int) ($options['storage_used_bytes'] ?? -1);
        $cachedAt = (int) ($options['storage_size_cached_at'] ?? 0);
        $cacheComplete = !empty($options['storage_size_complete'])
            && (int) ($options['storage_size_scan_version'] ?? 0) === self::SIZE_SCAN_VERSION;
        $hasCache = $cachedPath === $cacheKey
            && $cachedBytes >= 0
            && $cachedAt > 0
            && $cacheComplete;

        if (!$forceRefresh && $hasCache)
            return ['bytes' => $cachedBytes, 'cached' => true, 'complete' => true, 'pending' => false];

        if ($cacheOnly)
            return ['bytes' => max(0, $cachedBytes), 'cached' => $hasCache, 'complete' => $hasCache, 'pending' => !$hasCache];

        $bytes = self::databaseUsedBytes();
        self::persistUsedBytesCache($cacheKey, $bytes, true);

        return ['bytes' => $bytes, 'cached' => false, 'complete' => true, 'pending' => false];
    }

    private static function refreshDatabaseUsedBytes(bool $forceRefresh = false): void
    {
        $options = Config::name('options')->get() ?? [];
        self::resolveDatabaseUsedBytes($options, $forceRefresh);
    }

    private static function resolveDirectoryUsedBytes(
        string $normalizedPath,
        array $options,
        bool $forceRefresh = false,
        bool $cacheOnly = false
    ): array {
        $cachedPath = (string) ($options['storage_size_path'] ?? '');
        $cachedBytes = (int) ($options['storage_used_bytes'] ?? -1);
        $cachedAt = (int) ($options['storage_size_cached_at'] ?? 0);
        $cacheComplete = !empty($options['storage_size_complete'])
            && (int) ($options['storage_size_scan_version'] ?? 0) === self::SIZE_SCAN_VERSION;
        $hasCache = $cachedPath === $normalizedPath
            && $cachedBytes >= 0
            && $cachedAt > 0
            && $cacheComplete;

        if (!$forceRefresh && $hasCache)
            return ['bytes' => $cachedBytes, 'cached' => true, 'complete' => true, 'pending' => false];

        if ($cacheOnly)
            return ['bytes' => max(0, $cachedBytes), 'cached' => $hasCache, 'complete' => $hasCache, 'pending' => !$hasCache];

        $scanSeconds = $forceRefresh ? 120 : 30;
        $fastOnly = !$forceRefresh;
        $scan = self::directorySizeBytes($normalizedPath, $scanSeconds, $fastOnly);

        if ($scan['complete']) {
            self::persistUsedBytesCache($normalizedPath, $scan['bytes'], true);

            return ['bytes' => $scan['bytes'], 'cached' => false, 'complete' => true, 'pending' => false];
        }

        if ($hasCache)
            return ['bytes' => max(0, $cachedBytes), 'cached' => true, 'complete' => false, 'pending' => false];

        return ['bytes' => 0, 'cached' => false, 'complete' => false, 'pending' => true];
    }

    private static function refreshDirectoryUsedBytes(string $normalizedPath, bool $forceRefresh = false): void
    {
        $options = Config::name('options')->get() ?? [];
        self::resolveDirectoryUsedBytes($normalizedPath, $options, $forceRefresh);
    }

    private static function persistUsedBytesCache(string $normalizedPath, int $bytes, bool $complete = true): void
    {
        Config::name('options')
            ->set('storage_used_bytes', max(0, $bytes))
            ->set('storage_size_cached_at', time())
            ->set('storage_size_path', $normalizedPath)
            ->set('storage_size_complete', $complete)
            ->set('storage_size_scan_version', self::SIZE_SCAN_VERSION)
            ->save();
    }

    private static function rootRealPath(): ?string
    {
        $root = realpath(self::defaultPath());

        return $root ?: null;
    }

    private static function rootPath(): string
    {
        $root = self::rootRealPath();

        return $root ? self::normalizePath($root) : self::normalizePath(self::defaultPath());
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private static function isReadableDirectory(?string $path): bool
    {
        return $path !== null && is_dir($path) && is_readable($path);
    }
}
