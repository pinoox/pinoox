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

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Helpers\EnvFile;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Portal\Config;
use Pinoox\Support\SystemConfig;

/**
 * Single entry point for installer database credentials.
 *
 * - system/config/database.config.php: persisted for all environments
 * - runtime (putenv): current request only so migrate/setup can connect
 * - .env: never created or modified by the installer
 */
class DatabaseCredentialsSync
{
    /**
     * @param array<string, mixed> $config
     */
    public static function persist(array $config): bool
    {
        $mode = self::resolveRuntimeMode();
        $env = self::toEnvVariables($config, $mode);

        EnvFile::forProject()->applyToRuntime($env);
        SystemConfig::clearCache();

        Config::name('~database')
            ->set('production', $config)
            ->set('development', $config)
            ->save();

        return true;
    }

    public static function resolveRuntimeMode(): string
    {
        try {
            $global = RuntimeMode::readGlobal();
            $mode = (string) ($global['mode'] ?? '');

            if ($mode !== '') {
                return RuntimeMode::normalize($mode);
            }
        } catch (\Throwable) {
        }

        $fromEnv = SystemConfig::env('PINOOX_MODE', SystemConfig::env('APP_ENV', null));

        if (is_string($fromEnv) && $fromEnv !== '') {
            return RuntimeMode::normalize($fromEnv);
        }

        return RuntimeMode::DEVELOPMENT;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, scalar|null>
     */
    public static function toEnvVariables(array $config, ?string $mode = null): array
    {
        $mode = RuntimeMode::normalize($mode ?? self::resolveRuntimeMode());
        $connection = self::databaseConnectionKey($mode);

        $driver = (string) ($config['driver'] ?? 'mysql');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? 'root');
        $password = $config['password'] ?? '';
        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        $collation = (string) ($config['collation'] ?? 'utf8mb4_bin');
        $prefix = (string) ($config['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX);
        $strict = $config['strict'] ?? true;
        $engine = $config['engine'] ?? null;
        $timezone = (string) ($config['timezone'] ?? '+03:30');

        return [
            'PINOOX_MODE' => $mode,
            'DB_CONNECTION' => $connection,
            'DB_DRIVER' => $driver,
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'DB_CHARSET' => $charset,
            'DB_COLLATION' => $collation,
            'DB_PREFIX' => $prefix,
            'DB_STRICT' => $strict,
            'DB_ENGINE' => $engine,
            'DB_TIMEZONE' => $timezone,
        ];
    }

    private static function databaseConnectionKey(string $mode): string
    {
        return match (RuntimeMode::normalize($mode)) {
            RuntimeMode::PRODUCTION => RuntimeMode::PRODUCTION,
            RuntimeMode::STAGING => RuntimeMode::STAGING,
            RuntimeMode::TEST => RuntimeMode::TEST,
            default => RuntimeMode::DEVELOPMENT,
        };
    }
}
