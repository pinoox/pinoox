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

use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;

function installerIntegrationEnabled(): bool
{
    $flag = getenv('PINOOX_INSTALLER_INTEGRATION') ?: ($_ENV['PINOOX_INSTALLER_INTEGRATION'] ?? null);

    return in_array(strtolower((string) $flag), ['1', 'true', 'yes'], true);
}

/**
 * @return array<string, mixed>|null
 */
function installerIntegrationDatabaseConfig(): ?array
{
    if (!installerIntegrationEnabled()) {
        return null;
    }

    try {
        pinooxBoot();
        $config = Config::name('~database')->get('development');

        if (!is_array($config) || ($config['driver'] ?? '') !== 'mysql') {
            return null;
        }

        if (empty($config['database'])) {
            return null;
        }

        return $config;
    } catch (Throwable) {
        return null;
    }
}

/**
 * @param array<string, mixed> $config
 */
function installerRegisterDatabase(array $config): void
{
    $manager = DB::___();

    foreach (['default', 'platform'] as $connection) {
        try {
            $manager->getDatabaseManager()->purge($connection);
        } catch (Throwable) {
        }
    }

    $manager->registerCoreConnection($config);
    DB::setAsGlobal();
    DB::bootEloquent();
}

/**
 * @param array<string, mixed> $config
 */
function installerDropPrefixedTables(array $config): void
{
    $prefix = (string) ($config['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX);
    $database = (string) ($config['database'] ?? '');

    if ($database === '' || $prefix === '') {
        return;
    }

    installerRegisterDatabase($config);

    $connection = DB::connection('platform');
    $rows = $connection->select(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?',
        [$database, $prefix . '%'],
    );

    if ($rows === []) {
        return;
    }

    $connection->statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        foreach ($rows as $row) {
            $table = $row->table_name ?? array_values((array) $row)[0] ?? null;

            if (is_string($table) && $table !== '') {
                $connection->statement('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
            }
        }
    } finally {
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

/**
 * @template TReturn
 * @param callable(array<string, mixed>): TReturn $callback
 * @return TReturn
 */
function withInstallerTestDatabase(callable $callback)
{
    $base = installerIntegrationDatabaseConfig();

    if ($base === null) {
        test()->markTestSkipped('Set PINOOX_INSTALLER_INTEGRATION=1 and configure MySQL development DB to run installer integration tests.');
    }

    $config = array_merge($base, [
        'prefix' => 'pinx_it_' . substr(md5((string) microtime(true)), 0, 10) . '_',
    ]);

    $projectRoot = dirname(__DIR__, 2);
    $envPath = $projectRoot . '/.env';
    $envBackup = is_file($envPath) ? file_get_contents($envPath) : null;
    $dbConfigPath = $projectRoot . '/pincore/config/database.config.php';
    $dbConfigBackup = is_file($dbConfigPath) ? file_get_contents($dbConfigPath) : null;

    try {
        installerDropPrefixedTables($config);
        installerRegisterDatabase($config);

        return $callback($config);
    } finally {
        try {
            installerDropPrefixedTables($config);
        } catch (Throwable) {
        }

        try {
            installerRegisterDatabase(array_merge($base, [
                'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            ]));
        } catch (Throwable) {
        }

        if ($envBackup !== null) {
            file_put_contents($envPath, $envBackup);
        }

        if ($dbConfigBackup !== null) {
            file_put_contents($dbConfigPath, $dbConfigBackup);
        }

        if (class_exists(\Pinoox\Support\SystemConfig::class)) {
            \Pinoox\Support\SystemConfig::clearCache();
        }
    }
}

function installerPhysicalTableExists(string $table, array $config): bool
{
    installerRegisterDatabase($config);

    $physical = DB::physicalTableName($table, 'platform');
    $connection = DB::connection('platform');
    $database = (string) $connection->getDatabaseName();

    if ($database === '' || $physical === '') {
        return false;
    }

    $row = $connection->selectOne(
        'SELECT 1 AS found FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
        [$database, $physical],
    );

    return $row !== null;
}

