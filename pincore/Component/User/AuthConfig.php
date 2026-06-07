<?php

namespace Pinoox\Component\User;

use Pinoox\Portal\App\App;
use Pinoox\Portal\Env;

class AuthConfig
{
    public const MODE_COOKIE = 'cookie';
    public const MODE_SESSION = 'session';
    public const MODE_JWT = 'jwt';

    private static ?array $resolved = null;

    /**
     * @return array{
     *     mode: string,
     *     key: string,
     *     lifetime: int,
     *     lifetime_unit: string,
     *     remember_lifetime: int,
     *     remember_unit: string,
     *     jwt_secret: string,
     *     provider: string,
     * }
     */
    public static function resolve(bool $refresh = false): array
    {
        if (self::$resolved !== null && !$refresh) {
            return self::$resolved;
        }

        $package = App::package();

        $mode = strtolower((string) (App::get('auth.mode') ?? self::MODE_COOKIE));
        $key = (string) (App::get('auth.key') ?? $package . '_pinoox');

        self::$resolved = [
            'mode' => $mode,
            'key' => $key,
            'lifetime' => (int) (App::get('auth.lifetime') ?? 30),
            'lifetime_unit' => (string) (App::get('auth.lifetime_unit') ?? 'day'),
            'remember_lifetime' => (int) (App::get('auth.remember_lifetime') ?? 365),
            'remember_unit' => (string) (App::get('auth.remember_unit') ?? 'day'),
            'jwt_secret' => (string) (App::get('auth.jwt_secret')
                ?? Env::get('PINOOX_JWT_SECRET')
                ?? 'BAF55D93DF7A2B3AA64722AA85448424AAB5CF4214AD2899CD9440BEC9B44894'),
            'provider' => (string) (App::get('transport.user') ?? $package),
        ];

        return self::$resolved;
    }

    public static function reset(): void
    {
        self::$resolved = null;
    }
}
