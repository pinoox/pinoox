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

use Pinoox\Component\Database\DatabaseConfig;
use Pinoox\Component\Store\Baker\EnvSensitiveConfig;
use Pinoox\Component\Store\Config\Strategy\FileConfigStrategy;
use Pinoox\Portal\Config;
use Pinoox\Support\SystemConfig;

/**
 * Persists installer database credentials to Pinker.
 *
 * Writes the selected connection profile (e.g. connections.mariadb) and sets
 * config default to that connection. Project `.env` stays developer-owned;
 * when env keys exist they still override at runtime in development/test.
 */
class DatabaseCredentialsSync
{
    /**
     * @param array<string, mixed> $config Normalized runtime connection config
     */
    public static function persist(array $config, string $connectionName = null): bool
    {
        $connectionName = self::resolveConnectionName($connectionName);

        try {
            $database = Config::name('~database');
            $strategy = $database->getStrategy();

            if (!$strategy instanceof FileConfigStrategy) {
                return false;
            }

            $pinker = $strategy->getPinker();
            $overridePath = $pinker->getOverrideFile();
            $overrideBackup = is_string($overridePath) && is_file($overridePath)
                ? file_get_contents($overridePath)
                : null;

            try {
                $pinker->restore();
                $database->restore();

                $root = $database->all();

                if (!is_array($root)) {
                    $root = [];
                }

                $root['default'] = $connectionName;

                $connections = is_array($root['connections'] ?? null) ? $root['connections'] : [];
                $connections[$connectionName] = array_replace(
                    is_array($connections[$connectionName] ?? null) ? $connections[$connectionName] : [],
                    self::storageConfig($config, $connectionName),
                );
                $root['connections'] = $connections;

                $database->setData($root);

                $pinker->forceOverridePaths([
                    'default' => $connectionName,
                ]);

                $pinker->info([
                    'env_sensitive' => 'yes',
                    'env_priority' => EnvSensitiveConfig::envPriorityLabel(),
                    'env_resolution' => EnvSensitiveConfig::resolutionLabel(),
                    'stored_profiles' => 'default,' . DatabaseConfig::pinkerPathForConnection($connectionName),
                ]);

                $database->save();
                SystemConfig::clearCache();

                return true;
            } catch (\Throwable $e) {
                if ($overrideBackup !== null && is_string($overridePath)) {
                    file_put_contents($overridePath, $overrideBackup);
                    SystemConfig::clearCache();
                }

                throw $e;
            }
        } catch (\Throwable) {
            return false;
        }
    }

    private static function resolveConnectionName(?string $connectionName): string
    {
        $connectionName = strtolower(trim((string) ($connectionName ?? DatabaseConfig::DEFAULT_CONNECTION)));

        return in_array($connectionName, InstallerDatabase::INSTALLABLE_CONNECTIONS, true)
            ? $connectionName
            : DatabaseConfig::DEFAULT_CONNECTION;
    }

    /**
     * Pinker stores the logical driver (mariadb stays mariadb); runtime may normalize to mysql.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function storageConfig(array $config, string $connectionName): array
    {
        $stored = $config;
        $stored['driver'] = match ($connectionName) {
            'mariadb' => 'mariadb',
            'pgsql' => 'pgsql',
            'sqlsrv' => 'sqlsrv',
            default => 'mysql',
        };

        return $stored;
    }
}
