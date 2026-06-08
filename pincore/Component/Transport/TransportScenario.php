<?php

namespace Pinoox\Component\Transport;

/**
 * Transport scenarios (single-word bundles) and granular keys (multi-word resources).
 *
 * app.php → transport.{scenario|granular_key} = {scope}
 * Scope: local | platform | host | {package}
 */
final class TransportScenario
{
    // ── Scenarios (single-word presets) ─────────────────────────────────

    /** Every granular resource. */
    public const FULL = 'full';

    /** Login system: accounts + auth + session tokens. */
    public const USER = 'user';

    /** Uploads and file metadata. */
    public const STORAGE = 'storage';

    /** Roles, permissions, and access checks. */
    public const ACCESS = 'access';

    // ── Granular keys (multi-word, specific) ────────────────────────────

    /** UserModel `app` column / global scope. */
    public const USER_TABLE = 'user_table';

    /** Auth manifest source: mode, JWT secret, lifetimes. */
    public const AUTH_CONFIG = 'auth_config';

    /** Auth cookie / client key name (`auth.key`) source. */
    public const AUTH_COOKIE = 'auth_cookie';

    /** TokenModel `app` column / DB session rows. */
    public const SESSION_TOKEN = 'session_token';

    /** FileModel `app` column / upload scope. */
    public const FILE_STORAGE = 'file_storage';

    /** Role & permission models `app` column scope. */
    public const ACCESS_TABLE = 'access_table';

    /**
     * Scenario → granular keys (scenarios are composed only from granular keys).
     *
     * @return array<string, list<string>>
     */
    public static function definitions(): array
    {
        return [
            self::USER => [
                self::USER_TABLE,
                self::AUTH_CONFIG,
                self::AUTH_COOKIE,
                self::SESSION_TOKEN,
            ],
            self::STORAGE => [
                self::FILE_STORAGE,
            ],
            self::ACCESS => [
                self::ACCESS_TABLE,
            ],
            self::FULL => self::granularKeys(),
        ];
    }

    /** @return list<string> */
    public static function scenarioNames(): array
    {
        return [self::USER, self::STORAGE, self::ACCESS, self::FULL];
    }

    /** @return list<string> */
    public static function granularKeys(): array
    {
        return [
            self::USER_TABLE,
            self::AUTH_CONFIG,
            self::AUTH_COOKIE,
            self::SESSION_TOKEN,
            self::FILE_STORAGE,
            self::ACCESS_TABLE,
        ];
    }

    /** @return array<string, string> */
    public static function granularLabels(): array
    {
        return [
            self::USER_TABLE => 'User table scope',
            self::AUTH_CONFIG => 'Auth settings (mode, JWT, lifetimes)',
            self::AUTH_COOKIE => 'Auth client key / cookie name',
            self::SESSION_TOKEN => 'Session token rows in DB',
            self::FILE_STORAGE => 'File uploads and metadata',
            self::ACCESS_TABLE => 'Roles and permissions',
        ];
    }

    public static function describes(string $scenario): string
    {
        $keys = implode(', ', self::keysForScenario($scenario));

        return match ($scenario) {
            self::FULL => 'All granular resources: ' . $keys,
            self::USER => 'User & login: ' . $keys,
            self::STORAGE => 'File storage: ' . $keys,
            self::ACCESS => 'Access control: ' . $keys,
            default => '',
        };
    }

    /** @return list<string> */
    public static function keysForScenario(string $scenario): array
    {
        return self::definitions()[$scenario] ?? [];
    }

    /** @return list<string> */
    public static function scenariosForGranularKey(string $granularKey): array
    {
        $scenarios = [];

        foreach (self::definitions() as $scenario => $keys) {
            if (in_array($granularKey, $keys, true)) {
                $scenarios[] = $scenario;
            }
        }

        return $scenarios;
    }

    public static function isScenario(string $name): bool
    {
        return array_key_exists($name, self::definitions());
    }

    public static function isGranularKey(string $name): bool
    {
        return in_array($name, self::granularKeys(), true);
    }

    /** Granular keys that participate in auth-source resolution. */
    public static function isAuthRelated(string $granularKey): bool
    {
        return in_array($granularKey, [self::AUTH_CONFIG, self::AUTH_COOKIE], true);
    }
}
