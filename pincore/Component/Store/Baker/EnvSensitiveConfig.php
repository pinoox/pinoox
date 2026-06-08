<?php

namespace Pinoox\Component\Store\Baker;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Support\SystemConfig;

/**
 * Env-backed config files (e.g. database.config.php).
 *
 * Pinker override metadata marks these files as env-sensitive. At runtime, any
 * .env key that maps to a config path wins over the Pinker value for that path.
 */
final class EnvSensitiveConfig
{
    public const ENV_PRIORITY = 'env-over-pinker';

    public static function sourceUsesEnv(string $mainFile): bool
    {
        if (!is_file($mainFile)) {
            return false;
        }

        $source = (string) file_get_contents($mainFile);

        return preg_match('/\\b(?:env|_env|config_env|getenv)\\s*\\(/', $source) === 1;
    }

    public static function shouldResolveFromEnv(): bool
    {
        return in_array(self::currentMode(), [RuntimeMode::DEVELOPMENT, RuntimeMode::TEST], true);
    }

    public static function currentMode(): string
    {
        return RuntimeMode::fromEnv();
    }

    /** @return list<string> Pinker dot-path prefixes for persisted credentials */
    public static function storedProfiles(): array
    {
        return \Pinoox\Component\Database\DatabaseConfig::pinkerStoredPaths();
    }

    public static function envPriorityLabel(): string
    {
        return self::ENV_PRIORITY;
    }

    public static function resolutionLabel(): string
    {
        return 'test=env-only;all-modes:defined-env-key-overrides-pinker';
    }

    public static function envIsDefined(string $key): bool
    {
        if (array_key_exists($key, $_ENV)) {
            return true;
        }

        if (array_key_exists($key, $_SERVER)) {
            return true;
        }

        return getenv($key) !== false;
    }

    public static function shouldSkipPinkerPath(string $mainFile, string $dotPath): bool
    {
        if (!self::sourceUsesEnv($mainFile)) {
            return false;
        }

        if (RuntimeMode::fromEnv() === RuntimeMode::TEST) {
            return true;
        }

        $envKey = self::envKeyForConfigPath($mainFile, $dotPath);

        return $envKey !== null && self::envIsDefined($envKey);
    }

    public static function envKeyForConfigPath(string $mainFile, string $dotPath): ?string
    {
        $basename = basename(str_replace('\\', '/', $mainFile));

        return match ($basename) {
            'database.config.php' => self::envKeyForDatabasePath($dotPath),
            default => null,
        };
    }

    private static function envKeyForDatabasePath(string $path): ?string
    {
        if ($path === 'default') {
            return 'DB_CONNECTION';
        }

        if ($path === 'migrations.table') {
            return 'DB_MIGRATIONS_TABLE';
        }

        if ($path === 'migrations.update_date_on_publish') {
            return 'DB_MIGRATIONS_UPDATE_DATE_ON_PUBLISH';
        }

        if (!preg_match('/^connections\.([^.]+)\.(.+)$/', $path, $matches)) {
            return null;
        }

        $connection = $matches[1];
        $field = $matches[2];

        return match ($field) {
            'url' => 'DB_URL',
            'host' => 'DB_HOST',
            'port' => 'DB_PORT',
            'database' => 'DB_DATABASE',
            'username' => 'DB_USERNAME',
            'password' => 'DB_PASSWORD',
            'unix_socket' => 'DB_SOCKET',
            'charset' => 'DB_CHARSET',
            'collation' => 'DB_COLLATION',
            'prefix' => 'DB_PREFIX',
            'prefix_indexes' => 'DB_PREFIX_INDEXES',
            'strict' => 'DB_STRICT',
            'engine' => 'DB_ENGINE',
            'timezone' => 'DB_TIMEZONE',
            'search_path' => 'DB_SCHEMA',
            'sslmode' => 'DB_SSLMODE',
            'driver' => $connection === 'mysql' ? 'DB_DRIVER' : null,
            default => null,
        };
    }
}
