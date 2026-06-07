<?php

namespace Pinoox\Component\Log;

use Monolog\Level;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Mode;
use Pinoox\Support\SystemConfig;

class LogConfig
{
    /**
     * @return array{
     *     path: string,
     *     channel: string,
     *     package: string,
     *     level: Level,
     *     rotate: bool,
     *     max_files: int
     * }
     */
    public static function resolve(): array
    {
        $global = config('~pinoox')->get('log') ?? [];
        $app = App::get('log') ?? [];

        if (!is_array($global)) {
            $global = [];
        }

        if (!is_array($app)) {
            $app = [];
        }

        $app = array_filter($app, static fn ($value) => $value !== null);
        $merged = array_merge($global, $app);
        $package = (string) (App::package() ?? 'pinoox');

        $path = (string) ($merged['path'] ?? '~storage/logs/pinoox.log');
        $path = str_replace('{package}', $package, $path);

        if (str_starts_with($path, '~')) {
            $path = SystemConfig::resolvePath($path);
        }

        self::ensureDirectory($path);

        return [
            'path' => $path,
            'channel' => (string) ($merged['channel'] ?? 'pinoox'),
            'package' => $package,
            'level' => self::parseLevel(self::resolveLevel($merged, $package)),
            'rotate' => (bool) ($merged['rotate'] ?? true),
            'max_files' => max(1, (int) ($merged['max_files'] ?? 14)),
        ];
    }

    public static function channelName(?string $suffix = null): string
    {
        $config = self::resolve();
        $base = $config['channel'] . '.' . $config['package'];

        return $suffix ? $base . '.' . $suffix : $base;
    }

    public static function path(): string
    {
        return self::resolve()['path'];
    }

    /**
     * Resolve log file path for a specific package without booting that app.
     */
    public static function resolveForPackage(string $package): array
    {
        $global = config('~pinoox')->get('log') ?? [];

        if (!is_array($global)) {
            $global = [];
        }

        $app = [];

        if ($package !== 'platform' && \Pinoox\Portal\App\AppEngine::exists($package)) {
            $appConfig = \Pinoox\Portal\App\AppEngine::config($package)->get('log') ?? [];
            $app = is_array($appConfig) ? $appConfig : [];
        }

        $app = array_filter($app, static fn ($value) => $value !== null);
        $merged = array_merge($global, $app);

        $path = (string) ($merged['path'] ?? '~storage/logs/pinoox.log');
        $path = str_replace('{package}', $package, $path);

        if (str_starts_with($path, '~')) {
            $path = SystemConfig::resolvePath($path);
        }

        self::ensureDirectory($path);

        return [
            'path' => $path,
            'channel' => (string) ($merged['channel'] ?? 'pinoox'),
            'package' => $package,
            'level' => self::parseLevel(self::resolveLevel($merged, $package)),
            'rotate' => (bool) ($merged['rotate'] ?? true),
            'max_files' => max(1, (int) ($merged['max_files'] ?? 14)),
        ];
    }

    public static function parseLevel(string|Level $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }

        return Level::fromName(strtoupper(trim($level)));
    }

    /**
     * @param array<string, mixed> $merged
     */
    private static function resolveLevel(array $merged, ?string $package = null): string
    {
        $level = $merged['level'] ?? null;

        if ($level !== null && $level !== '') {
            return (string) $level;
        }

        try {
            return Mode::defaultLogLevel($package);
        } catch (\Throwable) {
            return RuntimeMode::readGlobal()['mode'] === RuntimeMode::PRODUCTION ? 'warning' : 'debug';
        }
    }

    private static function ensureDirectory(string $path): void
    {
        $directory = dirname($path);

        if ($directory !== '' && !is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
    }
}

