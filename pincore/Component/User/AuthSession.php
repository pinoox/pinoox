<?php

namespace Pinoox\Component\User;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pinoox\Component\Cookie;
use Pinoox\Component\Session;
use Pinoox\Component\Token;
use Pinoox\Portal\Lang;
use Pinoox\Model\TokenModel;
use Pinoox\Model\UserModel;

/**
 * Low-level session transport (cookie, PHP session, or JWT + DB token).
 */
class AuthSession
{

    public const COOKIE = 'cookie';

    public const SESSION = 'session';

    public const JWT = 'jwt';

    public static ?string $login_key = null;

    private static ?string $msg = null;

    private static string $type = self::COOKIE;

    private static int $lifetime = 86400;

    private static int $rememberLifetime = 86400 * 365;

    private static ?array $token = null;

    private static string|false|null $token_key = false;

    private static ?array $user = null;

    private static ?string $requestToken = null;

    private static bool $updateLifetime = true;

    private static bool $updateTokenKey = false;

    private static string $user_session_key = 'pinoox_user';

    private static string $secret_key = 'BAF55D93DF7A2B3AA64722AA85448424AAB5CF4214AD2899CD9440BEC9B44894';

    private static ?string $appliedFingerprint = null;

    /**
     * @param array{
     *     mode: string,
     *     key: string,
     *     lifetime: int,
     *     lifetime_unit: string,
     *     remember_lifetime?: int,
     *     remember_unit?: string,
     *     jwt_secret?: string,
     * } $config
     */
    public static function applyConfig(array $config): void
    {
        $fingerprint = AuthConfig::fingerprint($config);

        if (self::$appliedFingerprint !== null && self::$appliedFingerprint !== $fingerprint) {
            self::$token_key = false;
            self::$token = null;
            self::$user = null;
        }

        self::$appliedFingerprint = $fingerprint;

        $nextType = match ($config['mode']) {
            AuthConfig::MODE_JWT => self::JWT,
            AuthConfig::MODE_SESSION => self::SESSION,
            default => self::COOKIE,
        };

        if ($nextType !== self::$type) {
            self::type($nextType);
        }

        self::setUserSessionKey((string) $config['key']);
        self::lifeTime($config['lifetime'], $config['lifetime_unit']);

        if (!empty($config['remember_lifetime'])) {
            self::$rememberLifetime = self::toSeconds(
                (int) $config['remember_lifetime'],
                (string) ($config['remember_unit'] ?? 'day'),
            );
        }

        if (!empty($config['jwt_secret'])) {
            self::setJwtSecret((string) $config['jwt_secret']);
        }
    }

    public static function setJwtSecret(string $secret): void
    {
        self::$secret_key = $secret;
    }

    public static function updateLifetime(bool $status): void
    {
        self::$updateLifetime = $status;
    }

    public static function updateTokenKey(bool $status): void
    {
        self::$updateTokenKey = $status;
    }

    public static function type(string $value): void
    {
        self::$type = $value;
        self::reset();
    }

    public static function reset(): void
    {
        self::$token = null;
        self::$user = null;
        self::$token_key = false;
        self::$requestToken = null;
        self::$appliedFingerprint = null;
    }

    public static function setRequestToken(?string $token): void
    {
        self::$requestToken = ($token !== null && $token !== '') ? $token : null;
        self::$token_key = false;
        self::$token = null;
        self::$user = null;
    }

    /**
     * Store a JWT client token in the auth cookie so iframe navigations keep the session.
     */
    public static function persistClientJwt(string $jwt): void
    {
        if (self::$type !== self::JWT || $jwt === '') {
            return;
        }

        $jwt = self::normalizeBearerToken($jwt);

        Cookie::set(
            self::$user_session_key,
            $jwt,
            self::$lifetime,
        );
    }

    public static function isLoggedIn(): bool
    {
        return !empty(self::getToken());
    }

    public static function getToken(?string $field = null): mixed
    {
        if (empty(self::$token)) {
            $token_key = self::getTokenKey();
            if ($token_key) {
                self::$token = Token::get($token_key);
            }
        }

        if ($field !== null && $field !== '') {
            return self::$token[$field] ?? null;
        }

        return self::$token;
    }

    public static function getTokenKey(): string|false|null
    {
        if (!empty(self::$token_key)) {
            return self::$token_key;
        }

        self::$token_key = match (self::$type) {
            self::COOKIE => Cookie::get(self::$user_session_key),
            self::JWT => self::authToken(),
            self::SESSION => (function () {
                if (PHP_SESSION_ACTIVE !== session_status()) {
                    return false;
                }

                $token = Session::get(self::$user_session_key);

                return !empty($token) ? $token : false;
            })(),
            default => false,
        };

        return self::$token_key;
    }

    public static function authToken(?string $token = null): string|false
    {
        if ($token === null) {
            $token = self::resolveBearerToken();
            if (empty($token)) {
                return false;
            }
        }

        $token = self::normalizeBearerToken($token);

        try {
            $payload = JWT::decode($token, new Key(self::$secret_key, 'HS256'));
            $payloadArray = (array) $payload;
            $key = key($payloadArray);

            return (string) $payloadArray[$key];
        } catch (\Exception) {
        }

        return false;
    }

    public static function setUserSessionKey(string $key): void
    {
        if (self::$user_session_key === $key) {
            return;
        }

        self::$user_session_key = $key;
        self::$token_key = false;
        self::$token = null;
        self::$user = null;
    }

    public static function setToken(UserModel $user, bool $newKey = false, bool $remember = false): void
    {
        Token::lifeTime(self::$lifetime / 86400, 'day');

        $user->makeHidden('password');
        $user_id = $user->user_id;
        $token_key = self::getTokenKey();
        if ($newKey) {
            $token_key = null;
        }
        $token_key = Token::generate($user->toArray(), self::$user_session_key, $user_id, $token_key);
        self::setClientToken($token_key, $remember);
        self::$user = null;
    }

    private static function setClientToken(string $token_key, bool $remember = false): void
    {
        self::$token_key = $token_key;
        self::$login_key = $token_key;

        match (self::$type) {
            self::COOKIE => Cookie::set(
                self::$user_session_key,
                $token_key,
                $remember ? self::$rememberLifetime : 999999999,
            ),
            self::JWT => (function () use ($token_key, $remember) {
                self::$login_key = JWT::encode([
                    self::$user_session_key => $token_key,
                ], self::$secret_key, 'HS256');
                Cookie::set(
                    self::$user_session_key,
                    self::$login_key,
                    $remember ? self::$rememberLifetime : self::$lifetime,
                );
            })(),
            self::SESSION => (function () use ($token_key) {
                Session::lifeTime($remember ? self::$rememberLifetime : 999999999);
                if (Session::has()) {
                    Session::regenerateId(true);
                }
                Session::set(self::$user_session_key, $token_key);
            })(),
            default => null,
        };
    }

    public static function lifeTime(int $lifeTime, ?string $unitTime = null): void
    {
        self::$lifetime = self::toSeconds($lifeTime, $unitTime ?? 'day');
        Token::lifeTime($lifeTime, $unitTime);
    }

    public static function getTokenData(?string $field = null): mixed
    {
        $data = self::getToken('token_data');

        if ($field !== null && $field !== '') {
            return $data[$field] ?? null;
        }

        return $data;
    }

    public static function getMessage(): ?string
    {
        return self::$msg;
    }

    public static function get(?string $field = null): mixed
    {
        $token = self::getToken();
        $tokenData = self::getTokenData();
        $user_id = $tokenData['user_id'] ?? null;

        if ($user_id && empty(self::$user)) {
            $user = UserModel::where('user_id', $user_id)->first();
            if ($user && $user->status == UserModel::ACTIVE) {
                $user->makeHidden('password');
                self::$user = $user->toArray();
                if (self::$updateTokenKey) {
                    $token_key = Token::changeKey($token['token_key'], true, false);
                    self::setClientToken($token_key);
                }
            } else {
                self::logout();
            }
        }

        if ($field !== null && $field !== '') {
            return self::$user[$field] ?? null;
        }

        return self::$user;
    }

    public static function logout(): void
    {
        if (self::isLoggedIn()) {
            self::removeToken();
        }

        self::reset();
    }

    private static function removeToken(): void
    {
        $token_key = self::getTokenKey();
        if (empty($token_key)) {
            return;
        }

        Token::delete($token_key);
        if (!TokenModel::where('token_key', $token_key)->first()) {
            match (self::$type) {
                self::COOKIE, self::JWT => Cookie::destroy(self::$user_session_key),
                self::SESSION => (function () {
                    Session::remove(self::$user_session_key);
                    if (Session::has()) {
                        Session::regenerateId(true);
                    }
                })(),
                default => null,
            };
        }
    }

    public static function append(array|string $key, mixed $val = null): void
    {
        if (!self::isLoggedIn()) {
            return;
        }

        $token = self::getToken();
        $token_key = $token['token_key'];
        $token_data = $token['token_data'];

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $token_data[$k] = $v;
            }
        } else {
            $token_data[$key] = $val;
        }

        Token::setData($token_key, $token_data);
        self::$token = null;
    }

    public static function set(array $data): void
    {
        if (!self::isLoggedIn()) {
            return;
        }

        $token = self::getToken();
        Token::setData($token['token_key'], $data);
        self::$token = null;
    }

    private static function resolveBearerToken(): ?string
    {
        $header = self::authorizationHeader();
        if (!empty($header)) {
            return $header;
        }

        if (!empty(self::$requestToken)) {
            return self::$requestToken;
        }

        if (self::$type === self::JWT) {
            $cookie = Cookie::get(self::$user_session_key);
            if (!empty($cookie)) {
                return $cookie;
            }
        }

        return null;
    }

    private static function authorizationHeader(): ?string
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if (!empty($token)) {
                return $token;
            }
        }

        return $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? null;
    }

    private static function normalizeBearerToken(string $token): string
    {
        if (str_starts_with(strtolower($token), 'bearer ')) {
            return trim(substr($token, 7));
        }

        return trim($token);
    }

    private static function toSeconds(int $lifeTime, string $unitTime): int
    {
        return match ($unitTime) {
            'min', 'minute', 'minutes' => $lifeTime * 60,
            'hour', 'hours' => $lifeTime * 3600,
            'day', 'days' => $lifeTime * 86400,
            default => $lifeTime,
        };
    }
}

