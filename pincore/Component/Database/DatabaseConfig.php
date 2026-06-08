<?php

namespace Pinoox\Component\Database;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Support\SystemConfig;

/**
 * Laravel-style database config: default connection + named connections.
 *
 * Legacy mode profiles (development/production/…) are normalized on read.
 */
final class DatabaseConfig
{
    public const DEFAULT_CONNECTION = 'mysql';

    public const TEST_CONNECTION = 'sqlite';

    /**
     * Active connection name (from DB_CONNECTION or config default).
     *
     * @throws \InvalidArgumentException when the connection is not defined in config
     */
    public static function connectionName(): string
    {
        $requested = self::requestedConnectionName();
        $root = SystemConfig::get('database');

        if (!is_array($root)) {
            throw new \InvalidArgumentException('Database config is invalid.');
        }

        self::connectionConfig(self::normalize($root), $requested);

        return $requested;
    }

    /**
     * Raw connection name from env / APP_ENV heuristics (may be undefined in config).
     */
    public static function requestedConnectionName(): string
    {
        $fromEnv = SystemConfig::env('DB_CONNECTION');

        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $appEnv = SystemConfig::env('APP_ENV');

        if (!is_string($appEnv) || $appEnv === '') {
            $appEnv = RuntimeMode::fromEnv();
        } else {
            $appEnv = RuntimeMode::normalize($appEnv);
        }

        if ($appEnv === RuntimeMode::TEST) {
            return self::TEST_CONNECTION;
        }

        $root = SystemConfig::get('database');

        if (is_array($root)) {
            $root = self::normalize($root);
            $default = (string) ($root['default'] ?? '');

            if ($default !== '') {
                return $default;
            }
        }

        return self::DEFAULT_CONNECTION;
    }

    /**
     * @param array<string, mixed> $root
     * @return array<string, mixed>
     */
    public static function normalize(array $root): array
    {
        if (isset($root['connections']) && is_array($root['connections'])) {
            $root['default'] = self::normalizeDefaultKey((string) ($root['default'] ?? self::DEFAULT_CONNECTION));

            return self::mergeLegacyProfileKeys($root);
        }

        return self::fromLegacyProfiles($root);
    }

    /**
     * @param array<string, mixed> $root Normalized config root
     * @return array<string, mixed>
     */
    public static function connectionConfig(array $root, ?string $connection = null): array
    {
        $root = self::normalize($root);
        $connection = $connection ?? self::requestedConnectionName();
        $connections = $root['connections'] ?? [];

        if (isset($connections[$connection]) && is_array($connections[$connection])) {
            return self::normalizeConnectionDriver($connections[$connection]);
        }

        throw new \InvalidArgumentException('Database connection "' . $connection . '" is not defined.');
    }

    /** @return list<string> */
    public static function supportedConnections(): array
    {
        $root = SystemConfig::get('database');

        if (!is_array($root)) {
            return [self::DEFAULT_CONNECTION, self::TEST_CONNECTION];
        }

        $root = self::normalize($root);
        $names = array_keys($root['connections'] ?? []);

        return $names !== [] ? $names : [self::DEFAULT_CONNECTION, self::TEST_CONNECTION];
    }

    /**
     * Illuminate 10 has no native mariadb connector; MariaDB uses the MySQL protocol.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function normalizeConnectionDriver(array $config): array
    {
        if (($config['driver'] ?? null) === 'mariadb') {
            $config['driver'] = 'mysql';
        }

        return $config;
    }

    /** @return list<string> */
    public static function pinkerStoredPaths(): array
    {
        return [self::pinkerPathForConnection(self::DEFAULT_CONNECTION)];
    }

    public static function pinkerPathForConnection(string $connection = self::DEFAULT_CONNECTION): string
    {
        return 'connections.' . $connection;
    }

    /**
     * Pinker dot-path prefix for the primary mysql connection.
     * @deprecated Use {@see pinkerPathForConnection()}
     */
    public static function pinkerPathPrefix(): string
    {
        return self::pinkerPathForConnection(self::DEFAULT_CONNECTION);
    }

    /**
     * @param array<string, mixed> $config Connection driver config
     * @return array<string, scalar|null>
     */
    public static function toEnvVariables(array $config, ?string $appEnv = null): array
    {
        $appEnv = RuntimeMode::normalize($appEnv ?? (string) (SystemConfig::env('APP_ENV') ?: RuntimeMode::DEFAULT));

        return [
            'APP_ENV' => $appEnv,
            'DB_CONNECTION' => self::connectionNameFromEnvOrDefault($appEnv),
            'DB_DRIVER' => (string) ($config['driver'] ?? 'mysql'),
            'DB_HOST' => (string) ($config['host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($config['port'] ?? '3306'),
            'DB_DATABASE' => (string) ($config['database'] ?? ''),
            'DB_USERNAME' => (string) ($config['username'] ?? 'root'),
            'DB_PASSWORD' => $config['password'] ?? '',
            'DB_CHARSET' => (string) ($config['charset'] ?? 'utf8mb4'),
            'DB_COLLATION' => (string) ($config['collation'] ?? 'utf8mb4_bin'),
            'DB_PREFIX' => (string) ($config['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'DB_STRICT' => $config['strict'] ?? true,
            'DB_ENGINE' => $config['engine'] ?? null,
            'DB_TIMEZONE' => (string) ($config['timezone'] ?? '+03:30'),
        ];
    }

    private static function connectionNameFromEnvOrDefault(string $appEnv): string
    {
        return RuntimeMode::normalize($appEnv) === RuntimeMode::TEST
            ? self::TEST_CONNECTION
            : self::DEFAULT_CONNECTION;
    }

    /**
     * @param array<string, mixed> $root
     * @return array<string, mixed>
     */
    private static function fromLegacyProfiles(array $root): array
    {
        $connections = [];

        foreach (['production', 'staging', 'development'] as $profile) {
            if (isset($root[$profile]) && is_array($root[$profile])) {
                $connections[self::DEFAULT_CONNECTION] = $root[$profile];
                break;
            }
        }

        if (isset($root['test']) && is_array($root['test'])) {
            $connections[self::TEST_CONNECTION] = $root['test'];
        }

        if ($connections === []) {
            $connections[self::DEFAULT_CONNECTION] = [
                'driver' => 'mysql',
            ];
        }

        if (!isset($connections[self::TEST_CONNECTION])) {
            $connections[self::TEST_CONNECTION] = [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ];
        }

        $legacyDefault = (string) ($root['default'] ?? self::DEFAULT_CONNECTION);

        return [
            'default' => self::normalizeDefaultKey($legacyDefault),
            'connections' => $connections,
        ];
    }

    private static function normalizeDefaultKey(string $default): string
    {
        return match (RuntimeMode::normalize($default)) {
            RuntimeMode::TEST => self::TEST_CONNECTION,
            RuntimeMode::PRODUCTION, RuntimeMode::STAGING, RuntimeMode::DEVELOPMENT => self::DEFAULT_CONNECTION,
            default => $default !== '' ? $default : self::DEFAULT_CONNECTION,
        };
    }

    /**
     * Pinker overrides from before the connections.* layout may sit as top-level production.* keys.
     *
     * @param array<string, mixed> $root
     * @return array<string, mixed>
     */
    private static function mergeLegacyProfileKeys(array $root): array
    {
        $connections = is_array($root['connections'] ?? null) ? $root['connections'] : [];

        if (isset($root['test']) && is_array($root['test'])) {
            $connections[self::TEST_CONNECTION] = array_replace(
                $connections[self::TEST_CONNECTION] ?? [],
                $root['test'],
            );
            unset($root['test']);
        }

        foreach (['production', 'staging', 'development'] as $profile) {
            if (!isset($root[$profile]) || !is_array($root[$profile])) {
                continue;
            }

            $connections[self::DEFAULT_CONNECTION] = array_replace(
                $connections[self::DEFAULT_CONNECTION] ?? [],
                $root[$profile],
            );
            unset($root[$profile]);
        }

        $root['connections'] = $connections;

        return $root;
    }
}
