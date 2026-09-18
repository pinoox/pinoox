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
use Pinoox\Component\Database\PlatformDatabaseStore;

/**
 * Persists installer database credentials to pinker/stable.
 *
 * Project `.env` stays developer-owned; when env keys exist they still
 * override at runtime (env-over-pinker).
 */
class DatabaseCredentialsSync
{
    /**
     * @param array<string, mixed> $config Normalized runtime connection config
     */
    public static function persist(array $config, ?string $connectionName = null): bool
    {
        $connectionName = self::resolveConnectionName($connectionName);

        return PlatformDatabaseStore::saveConnection(
            $connectionName,
            self::storageConfig($config, $connectionName),
            true,
        );
    }

    private static function resolveConnectionName(?string $connectionName): string
    {
        $connectionName = strtolower(trim((string) ($connectionName ?? DatabaseConfig::DEFAULT_CONNECTION)));

        return in_array($connectionName, InstallerDatabase::INSTALLABLE_CONNECTIONS, true)
            ? $connectionName
            : DatabaseConfig::DEFAULT_CONNECTION;
    }

    /**
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
