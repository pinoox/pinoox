<?php

namespace App\com_pinoox_manager\Component;

use Pinoox\Component\Database\AppDatabaseResolver;
use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Pinx;

final class PackageDatabase
{
    /**
     * @return array<string, mixed>
     */
    public static function analyzeFromPinx(string $pinxFile, string $package): array
    {
        $packaged = self::readPackagedDatabase($pinxFile);
        $suggested = self::suggestPrefix($package);
        $resolved = self::resolvePrefix($package, $packaged['prefix'] ?? null);
        $used = self::collectUsedPrefixes($package);

        $packagedPrefix = $packaged['prefix'] ?? null;
        $normalizedPackaged = $packagedPrefix ? self::formatPrefix((string) $packagedPrefix) : null;
        $packagedConflict = $normalizedPackaged
            ? self::hasPrefixConflict($normalizedPackaged, $package, $used)
            : false;

        return [
            'packaged_prefix' => $packaged['prefix'],
            'suggested_prefix' => $suggested,
            'resolved_prefix' => $resolved,
            'used_prefixes' => $used,
            'conflict' => $packagedConflict,
            'needs_prefix_setup' => $packagedConflict
                || $normalizedPackaged === null
                || $resolved !== $normalizedPackaged,
            'tables_exist' => self::prefixTablesExist($resolved),
            'has_migrations' => $packaged['has_migrations'],
            'connection' => self::platformDefaults(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function platformDefaults(): array
    {
        try {
            DB::ensureRegistered();
            $config = DB::connection(DatabaseManager::CORE_CONNECTION)->getConfig();

            return [
                'connection' => (string) ($config['driver'] ?? 'mysql'),
                'host' => (string) ($config['host'] ?? '127.0.0.1'),
                'port' => (string) ($config['port'] ?? '3306'),
                'database' => (string) ($config['database'] ?? ''),
                'username' => (string) ($config['username'] ?? ''),
                'prefix' => (string) ($config['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            ];
        } catch (\Throwable) {
            return [
                'connection' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => '',
                'username' => 'root',
                'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            ];
        }
    }

    /**
     * @param array<string, mixed>|null $databaseOptions
     */
    public static function applyForPackage(string $package, ?array $databaseOptions = null): string
    {
        if (!AppEngine::exists($package)) {
            return self::suggestPrefix($package);
        }

        $config = AppEngine::config($package);
        $currentDatabase = $config->get('database');
        $currentTable = $config->get('table');
        $currentPrefix = AppDatabaseResolver::explicitPrefix(
            is_array($currentDatabase) ? $currentDatabase : null,
            is_array($currentTable) ? $currentTable : null,
        );

        $requestedPrefix = is_array($databaseOptions)
            ? trim((string) ($databaseOptions['prefix'] ?? ''))
            : '';

        $prefix = $requestedPrefix !== ''
            ? self::normalizePrefix($requestedPrefix)
            : self::resolvePrefix($package, $currentPrefix);

        if (is_array($databaseOptions) && self::hasCustomConnection($databaseOptions)) {
            $database = self::buildDatabaseBlock($databaseOptions, $prefix);
            $config->set('database', $database)->save();

            return $prefix;
        }

        if ($prefix === $currentPrefix && is_array($currentDatabase) && $currentDatabase !== []) {
            return $prefix;
        }

        $databaseBlock = self::buildPrefixOnlyBlock($prefix, $currentDatabase);

        if ($databaseBlock !== null) {
            $config->set('database', $databaseBlock)->save();
        } elseif (is_array($currentTable)) {
            $currentTable['prefix'] = $prefix;
            $config->set('table', $currentTable)->save();
        } else {
            $config->set('table', ['prefix' => $prefix])->save();
        }

        return $prefix;
    }

    public static function testConnection(array $input): bool
    {
        $connection = strtolower(trim((string) ($input['connection'] ?? 'mysql')));
        $port = (string) ($input['port'] ?? '');

        if ($port === '') {
            $port = match ($connection) {
                'pgsql' => '5432',
                'sqlsrv' => '1433',
                default => '3306',
            };
        }

        $config = [
            'driver' => $connection,
            'host' => (string) ($input['host'] ?? '127.0.0.1'),
            'port' => $port,
            'database' => (string) ($input['database'] ?? ''),
            'username' => (string) ($input['username'] ?? 'root'),
            'password' => (string) ($input['password'] ?? ''),
            'prefix' => self::normalizePrefix((string) ($input['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX)),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];

        if ($config['database'] === '') {
            return false;
        }

        try {
            DB::addConnection($config, '__manager_install_test');
            DB::bootEloquent();
            DB::connection('__manager_install_test')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function prefixTablesExist(string $prefix): bool
    {
        $prefix = self::normalizePrefix($prefix);

        if ($prefix === '') {
            return false;
        }

        try {
            DB::ensureRegistered();
            $tables = DB::connection()->getSchemaBuilder()->getTableListing();

            foreach ($tables as $table) {
                if (str_starts_with((string) $table, $prefix)) {
                    return true;
                }
            }
        } catch (\Throwable) {
        }

        return false;
    }

    public static function suggestPrefix(string $package): string
    {
        $parts = array_values(array_filter(explode('_', $package)));
        $name = end($parts) ?: $package;
        $name = preg_replace('/[^A-Za-z0-9_]+/', '_', strtolower((string) $name));

        return self::normalizePrefix(trim((string) $name, '_') . '_');
    }

    public static function resolvePrefix(string $package, ?string $packagedPrefix = null): string
    {
        $packagedPrefix = $packagedPrefix !== null && $packagedPrefix !== ''
            ? self::normalizePrefix($packagedPrefix)
            : null;

        $suggested = self::suggestPrefix($package);
        $used = self::collectUsedPrefixes($package);
        $generic = [
            self::normalizePrefix(DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'pinoox_',
            'pin_',
        ];

        $candidate = $packagedPrefix ?? $suggested;

        if ($packagedPrefix === null || in_array($packagedPrefix, $generic, true)) {
            $candidate = $suggested;
        }

        if (self::hasPrefixConflict($candidate, $package, $used)) {
            $candidate = $suggested;
        }

        return self::ensureUniquePrefix($candidate, $package, $used);
    }

    /**
     * @return array{prefix: ?string, has_migrations: bool}
     */
    private static function readPackagedDatabase(string $pinxFile): array
    {
        try {
            return Pinx::withReader($pinxFile, static function ($reader) {
                $zip = $reader->zip();
                $entry = PinxManifest::PAYLOAD_PREFIX . 'app.php';

                if (!$zip->hasEntry($entry)) {
                    return ['prefix' => null, 'has_migrations' => false];
                }

                $contents = $zip->getEntryContents($entry);
                $config = self::loadPhpReturn($contents);
                $database = is_array($config['database'] ?? null) ? $config['database'] : null;
                $table = is_array($config['table'] ?? null) ? $config['table'] : null;
                $hasMigrations = $zip->hasEntry(PinxManifest::PAYLOAD_PREFIX . 'database/migrations/')
                    || self::zipHasMigrationFiles($zip);

                return [
                    'prefix' => AppDatabaseResolver::explicitPrefix($database, $table),
                    'has_migrations' => $hasMigrations,
                ];
            });
        } catch (\Throwable) {
            return ['prefix' => null, 'has_migrations' => false];
        }
    }

    private static function zipHasMigrationFiles($zip): bool
    {
        foreach ($zip->getListFiles() as $file) {
            if (str_starts_with($file, PinxManifest::PAYLOAD_PREFIX . 'database/migrations/')
                && str_ends_with(strtolower($file), '.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private static function collectUsedPrefixes(?string $excludePackage = null): array
    {
        $used = [];

        foreach (AppEngine::all() as $app) {
            if (!$app->exists()) {
                continue;
            }

            $package = $app->package();

            if ($excludePackage !== null && $package === $excludePackage) {
                continue;
            }

            $database = $app->config()->get('database');
            $table = $app->config()->get('table');
            $prefix = AppDatabaseResolver::explicitPrefix(
                is_array($database) ? $database : null,
                is_array($table) ? $table : null,
            );

            if ($prefix === null || $prefix === '') {
                $prefix = self::suggestPrefix($package);
            }

            $prefix = self::normalizePrefix($prefix);
            $used[$package] = $prefix;
        }

        return $used;
    }

    /**
     * @param array<string, string> $used
     */
    private static function hasPrefixConflict(string $prefix, string $package, array $used): bool
    {
        $prefix = self::normalizePrefix($prefix);

        foreach ($used as $owner => $existing) {
            if ($owner === $package) {
                continue;
            }

            if ($existing === $prefix) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $used
     */
    private static function ensureUniquePrefix(string $prefix, string $package, array $used): string
    {
        $prefix = self::normalizePrefix($prefix);
        $base = rtrim($prefix, '_');

        if (!self::hasPrefixConflict($prefix, $package, $used)) {
            return $prefix;
        }

        for ($i = 2; $i <= 99; $i++) {
            $candidate = self::normalizePrefix($base . '_' . $i);

            if (!self::hasPrefixConflict($candidate, $package, $used)) {
                return $candidate;
            }
        }

        return self::normalizePrefix(self::suggestPrefix($package));
    }

    public static function formatPrefix(string $prefix): string
    {
        return self::normalizePrefix($prefix);
    }

    private static function normalizePrefix(string $prefix): string
    {
        $prefix = strtolower(trim($prefix));
        $prefix = preg_replace('/[^a-z0-9_]+/', '_', $prefix) ?? '';
        $prefix = trim($prefix, '_');

        if ($prefix === '') {
            return DatabaseManager::DEFAULT_CORE_TABLE_PREFIX;
        }

        return str_ends_with($prefix, '_') ? $prefix : $prefix . '_';
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function hasCustomConnectionOptions(array $options): bool
    {
        return self::hasCustomConnection($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function hasCustomConnection(array $options): bool
    {
        foreach (['host', 'database', 'username', 'password', 'port'] as $key) {
            if (!empty($options[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function buildDatabaseBlock(array $options, string $prefix): array
    {
        $connection = strtolower(trim((string) ($options['connection'] ?? 'mysql')));

        return array_filter([
            'driver' => $connection,
            'host' => (string) ($options['host'] ?? '127.0.0.1'),
            'port' => (string) ($options['port'] ?? ''),
            'database' => (string) ($options['database'] ?? ''),
            'username' => (string) ($options['username'] ?? 'root'),
            'password' => (string) ($options['password'] ?? ''),
            'prefix' => $prefix,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param array<string, mixed>|null $currentDatabase
     * @return array<string, mixed>|null
     */
    private static function buildPrefixOnlyBlock(string $prefix, ?array $currentDatabase): ?array
    {
        if (is_array($currentDatabase) && $currentDatabase !== []) {
            $keys = array_keys(array_filter(
                $currentDatabase,
                static fn ($value) => $value !== null && $value !== '',
            ));

            if (array_diff($keys, ['prefix', 'table_prefix', 'use', 'connection']) !== []) {
                return null;
            }
        }

        return ['prefix' => $prefix];
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadPhpReturn(string $contents): array
    {
        $file = tempnam(sys_get_temp_dir(), 'pinx_app_');

        if ($file === false) {
            return [];
        }

        try {
            file_put_contents($file, $contents);
            $loaded = include $file;

            return is_array($loaded) ? $loaded : [];
        } finally {
            @unlink($file);
        }
    }
}
