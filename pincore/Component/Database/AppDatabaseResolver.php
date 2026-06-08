<?php

namespace Pinoox\Component\Database;

use Pinoox\Support\SystemConfig;

/**
 * Normalizes app.php → database block into Illuminate connection configs.
 *
 * Modes:
 *  - null / omitted — platform default connection (no app registration)
 *  - use / connection — reuse a named platform connection (optional prefix & overrides)
 *  - driver + credentials — dedicated app connection
 *  - connections[] — multiple named app connections
 *  - prefix / table.prefix only — platform default connection with custom table prefix
 */
final class AppDatabaseResolver
{
    private const PLATFORM_ALIASES = ['platform', 'default', 'core'];

    /**
     * @param array<string, mixed>|null $database
     * @param array<string, mixed>|null $table
     * @param array<string, mixed>|null $platformDatabase Platform database.config root (for tests)
     * @return array<string, array<string, mixed>>
     */
    public static function resolve(?array $database, ?array $table = null, ?array $platformDatabase = null): array
    {
        $prefix = self::connectionPrefix($database) ?? self::tablePrefix($database, $table);

        if ($database === null || $database === []) {
            return $prefix !== null
                ? ['default' => self::platformDefaultWithPrefix($prefix, $platformDatabase)]
                : [];
        }

        if (isset($database['connections']) && is_array($database['connections'])) {
            return self::resolveNamedConnections($database, $platformDatabase);
        }

        $platformName = self::platformConnectionName($database);

        if ($platformName !== null) {
            $rawUse = strtolower(trim((string) ($database['use'] ?? $database['connection'] ?? '')));
            $isGenericPlatform = in_array($rawUse, self::PLATFORM_ALIASES, true);

            if ($isGenericPlatform && !self::hasConnectionOverrides($database, $prefix)) {
                return [];
            }

            $config = self::platformConnectionConfig($platformName, $platformDatabase);

            return ['default' => self::applyOverrides($config, $database, $prefix)];
        }

        if (isset($database['driver']) && is_string($database['driver']) && $database['driver'] !== '') {
            return ['default' => self::applyOverrides($database, [], self::connectionPrefix($database))];
        }

        $tablePrefix = self::tablePrefix($database, $table);
        if ($tablePrefix !== null && self::connectionPrefix($database) === null && self::platformConnectionName($database) === null) {
            return ['default' => self::platformDefaultWithPrefix($tablePrefix, $platformDatabase)];
        }

        $connectionPrefix = self::connectionPrefix($database);
        if ($connectionPrefix !== null) {
            return ['default' => self::platformDefaultWithPrefix($connectionPrefix, $platformDatabase)];
        }

        return self::filterValidConnections($database);
    }

    /**
     * Table naming prefix (table.prefix / database.table_prefix).
     *
     * @param array<string, mixed>|null $database
     * @param array<string, mixed>|null $table
     */
    public static function tablePrefix(?array $database, ?array $table = null): ?string
    {
        if (is_array($database) && array_key_exists('table_prefix', $database) && $database['table_prefix'] !== null && $database['table_prefix'] !== '') {
            return (string) $database['table_prefix'];
        }

        if (is_array($table) && array_key_exists('prefix', $table) && $table['prefix'] !== null && $table['prefix'] !== '') {
            return (string) $table['prefix'];
        }

        return null;
    }

    /**
     * Connection-level prefix override (database.prefix).
     *
     * @param array<string, mixed>|null $database
     */
    public static function connectionPrefix(?array $database): ?string
    {
        if (is_array($database) && array_key_exists('prefix', $database) && $database['prefix'] !== null && $database['prefix'] !== '') {
            return (string) $database['prefix'];
        }

        return null;
    }

    /**
     * Prefix applied when cloning the platform default connection.
     *
     * @param array<string, mixed>|null $database
     * @param array<string, mixed>|null $table
     */
    public static function explicitPrefix(?array $database, ?array $table = null): ?string
    {
        return self::connectionPrefix($database) ?? self::tablePrefix($database, $table);
    }

    /**
     * @param array<string, mixed> $database
     */
    public static function defaultConnectionName(array $database): string
    {
        $default = $database['default'] ?? 'default';

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    /**
     * @param array<string, mixed> $database
     * @param array<string, mixed>|null $platformDatabase
     * @return array<string, array<string, mixed>>
     */
    private static function resolveNamedConnections(array $database, ?array $platformDatabase): array
    {
        $connections = is_array($database['connections'] ?? null) ? $database['connections'] : [];
        $resolved = [];

        foreach ($connections as $name => $config) {
            if (!is_string($name) || $name === '' || !is_array($config)) {
                continue;
            }

            $resolved[$name] = self::resolveSingleConnection($config, $platformDatabase);
        }

        return self::filterValidConnections($resolved);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function resolveSingleConnection(array $config, ?array $platformDatabase): array
    {
        $platformName = self::platformConnectionName($config);

        if ($platformName !== null) {
            $base = self::platformConnectionConfig($platformName, $platformDatabase);

            return ['default' => self::applyOverrides($base, $config, self::connectionPrefix($config))];
        }

        return DatabaseConfig::normalizeConnectionDriver($config);
    }

    /**
     * @param array<string, mixed> $database
     */
    private static function platformConnectionName(array $database): ?string
    {
        $use = $database['use'] ?? $database['connection'] ?? null;

        if (!is_string($use) || trim($use) === '') {
            return null;
        }

        $use = strtolower(trim($use));

        if (in_array($use, self::PLATFORM_ALIASES, true)) {
            return self::platformDefaultConnectionName($database);
        }

        return $use;
    }

    /**
     * @param array<string, mixed>|null $platformDatabase
     */
    private static function platformDefaultConnectionName(?array $platformDatabase = null): string
    {
        if (is_array($platformDatabase)) {
            $normalized = DatabaseConfig::normalize($platformDatabase);

            return (string) ($normalized['default'] ?? DatabaseConfig::DEFAULT_CONNECTION);
        }

        try {
            return DatabaseConfig::connectionName();
        } catch (\Throwable) {
            $root = SystemConfig::get('database');

            if (is_array($root)) {
                $normalized = DatabaseConfig::normalize($root);

                return (string) ($normalized['default'] ?? DatabaseConfig::DEFAULT_CONNECTION);
            }

            return DatabaseConfig::DEFAULT_CONNECTION;
        }
    }

    /**
     * @param array<string, mixed>|null $platformDatabase
     * @return array<string, mixed>
     */
    private static function platformConnectionConfig(string $name, ?array $platformDatabase = null): array
    {
        $root = $platformDatabase ?? SystemConfig::get('database');

        if (!is_array($root)) {
            throw new \RuntimeException('Platform database config is not available.');
        }

        return DatabaseConfig::connectionConfig(DatabaseConfig::normalize($root), $name);
    }

    /**
     * @param array<string, mixed>|null $platformDatabase
     * @return array<string, mixed>
     */
    private static function platformDefaultWithPrefix(string $prefix, ?array $platformDatabase = null): array
    {
        $config = self::platformConnectionConfig(
            self::platformDefaultConnectionName($platformDatabase),
            $platformDatabase,
        );
        $config['prefix'] = $prefix;

        return $config;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $database
     */
    private static function applyOverrides(array $base, array $database, ?string $prefix = null): array
    {
        $config = DatabaseConfig::normalizeConnectionDriver($base);

        foreach (self::metaKeys() as $key) {
            unset($database[$key]);
        }

        if ($prefix !== null) {
            $config['prefix'] = $prefix;
        } elseif (isset($database['prefix']) && $database['prefix'] !== null && $database['prefix'] !== '') {
            $config['prefix'] = (string) $database['prefix'];
            unset($database['prefix']);
        }

        foreach ($database as $key => $value) {
            if ($value === null) {
                continue;
            }

            $config[$key] = $value;
        }

        return DatabaseConfig::normalizeConnectionDriver($config);
    }

    /**
     * @param array<string, mixed> $database
     */
    private static function hasConnectionOverrides(array $database, ?string $prefix): bool
    {
        if ($prefix !== null) {
            return true;
        }

        foreach ($database as $key => $value) {
            if ($value === null || in_array($key, self::metaKeys(), true)) {
                continue;
            }

            if (!in_array($key, ['prefix', 'table_prefix'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function metaKeys(): array
    {
        return ['use', 'connection', 'connections', 'default', 'table_prefix'];
    }

    /**
     * @param array<string, mixed> $connections
     * @return array<string, array<string, mixed>>
     */
    private static function filterValidConnections(array $connections): array
    {
        $valid = [];

        foreach ($connections as $name => $config) {
            if (!is_string($name) || $name === '' || !is_array($config)) {
                continue;
            }

            if (!empty($config['driver'])) {
                $valid[$name] = DatabaseConfig::normalizeConnectionDriver($config);
            }
        }

        return $valid;
    }
}
