<?php

namespace Pinoox\Component\Package\AppEnv;

use Pinoox\Component\Runtime\RuntimeMode;

/**
 * Standard keys for apps/{package}/.env and apps/{package}/theme/{name}/.env.
 *
 * Theme .env overrides app .env for the same keys.
 */
final class AppEnvCatalog
{
    /** @var array<string, array{path: string, type: string}> */
    public const KEYS = [
        'THEME' => ['path' => 'theme', 'type' => 'string'],
        'MODE' => ['path' => 'runtime.mode', 'type' => 'mode'],
        'DEBUG' => ['path' => 'runtime.debug', 'type' => 'bool'],
        'LANG' => ['path' => 'lang', 'type' => 'string'],
        'ENABLE' => ['path' => 'enable', 'type' => 'bool'],
        'CACHE_ENABLED' => ['path' => 'cache.enabled', 'type' => 'bool'],
        'CACHE_MODE' => ['path' => 'cache.mode', 'type' => 'mode'],
        'DB_USE' => ['path' => 'database.use', 'type' => 'string'],
        'DB_PREFIX' => ['path' => 'database.prefix', 'type' => 'string'],
        'DB_DRIVER' => ['path' => 'database.driver', 'type' => 'string'],
        'DB_HOST' => ['path' => 'database.host', 'type' => 'string'],
        'DB_PORT' => ['path' => 'database.port', 'type' => 'string'],
        'DB_DATABASE' => ['path' => 'database.database', 'type' => 'string'],
        'DB_USERNAME' => ['path' => 'database.username', 'type' => 'string'],
        'DB_PASSWORD' => ['path' => 'database.password', 'type' => 'string'],
        'DB_CHARSET' => ['path' => 'database.charset', 'type' => 'string'],
        'DB_COLLATION' => ['path' => 'database.collation', 'type' => 'string'],
    ];

    /** @return list<string> */
    public static function keyNames(): array
    {
        return array_keys(self::KEYS);
    }

    public static function cast(string $key, mixed $raw): mixed
    {
        $spec = self::KEYS[strtoupper($key)] ?? null;

        if ($spec === null) {
            return $raw;
        }

        if ($raw === null || $raw === '') {
            return null;
        }

        $value = is_string($raw) ? trim($raw) : $raw;

        return match ($spec['type']) {
            'bool' => self::toBool($value),
            'mode' => RuntimeMode::normalize((string) $value),
            default => (string) $value,
        };
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', '(true)', 'yes', 'on' => true,
            '0', 'false', '(false)', 'no', 'off' => false,
            default => (bool) $value,
        };
    }
}
