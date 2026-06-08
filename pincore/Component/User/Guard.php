<?php

namespace Pinoox\Component\User;

use Pinoox\Component\User\Event\UserAuthenticated;
use Pinoox\Component\User\Event\UserLoggedOut;
use Pinoox\Component\User\Event\UserLoginFailed;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Lang;
use Pinoox\Model\UserModel;

class Guard
{
    private bool $booted = false;

    private ?string $bootedPackage = null;

    private ?string $bootedFingerprint = null;

    public function __construct(private readonly UserProvider $provider = new UserProvider())
    {
    }

    public function boot(): void
    {
        $package = App::package();
        AuthConfig::reset();
        $config = AuthConfig::resolve(refresh: true);
        $fingerprint = AuthConfig::fingerprint($config);

        if ($this->booted
            && $this->bootedPackage === $package
            && $this->bootedFingerprint === $fingerprint) {
            return;
        }

        AuthSession::applyConfig($config);
        $this->booted = true;
        $this->bootedPackage = $package;
        $this->bootedFingerprint = $fingerprint;
    }

    public function check(): bool
    {
        $this->boot();

        return AuthSession::isLoggedIn();
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function attempt(array $credentials, bool $remember = false): LoginResult
    {
        return $this->attemptResult($credentials, $remember);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function attemptResult(array $credentials, bool $remember = false): LoginResult
    {
        $this->boot();

        if ($this->check()) {
            return LoginResult::fail(
                'already_logged_in',
                Lang::get('~user.already_logged_in'),
            );
        }

        $user = $this->provider->retrieveByCredentials($credentials);
        $password = (string) ($credentials['password'] ?? '');

        if (!$user || !$this->provider->validateCredentials($user, $password)) {
            UserLoginFailed::dispatch([
                'login' => $credentials['username'] ?? $credentials['email'] ?? $credentials['login'] ?? null,
            ], 'invalid_credentials');

            return LoginResult::fail(
                'invalid_credentials',
                Lang::get('~user.username_or_password_is_wrong'),
            );
        }

        $token = $this->login($user, $remember);

        return LoginResult::ok((string) $token, $user);
    }

    public function login(UserModel $user, bool $remember = false): ?string
    {
        $this->boot();
        AuthSession::setToken($user, remember: $remember);
        UserAuthenticated::dispatch($user);

        return AuthSession::$login_key;
    }

    public function loginUsingId(int $userId, bool $remember = false): bool
    {
        $user = $this->provider->retrieveById($userId);
        if (!$user || $user->status !== UserModel::ACTIVE) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    public function logout(): void
    {
        $this->boot();
        $user = $this->user();
        AuthSession::logout();

        if ($user) {
            UserLoggedOut::dispatch($user);
        }
    }

    public function user(): ?UserModel
    {
        $this->boot();
        $userId = AuthSession::get('user_id');

        return $userId ? $this->provider->retrieveById((int) $userId) : null;
    }

    public function id(): ?int
    {
        $id = AuthSession::get('user_id');

        return $id !== null ? (int) $id : null;
    }

    public function token(): ?string
    {
        return AuthSession::$login_key;
    }

    public function reset(): void
    {
        AuthSession::reset();
        AuthConfig::reset();
        $this->booted = false;
        $this->bootedPackage = null;
        $this->bootedFingerprint = null;
    }

    public function setRequestToken(?string $token): void
    {
        $this->boot();
        AuthSession::setRequestToken($token);
    }

    public function persistClientJwt(string $jwt): void
    {
        $this->boot();

        if (!$this->check()) {
            return;
        }

        AuthSession::persistClientJwt($jwt);
    }

    public function get(?string $field = null): mixed
    {
        $this->boot();

        return AuthSession::get($field);
    }

    public function session(?string $field = null): mixed
    {
        $this->boot();

        return AuthSession::getToken($field);
    }

    public function sessionData(?string $field = null): mixed
    {
        $this->boot();

        return AuthSession::getTokenData($field);
    }

    public function sessionPut(array|string $key, mixed $value = null): void
    {
        $this->boot();
        AuthSession::append($key, $value);
    }

    public function sessionReplace(array $data): void
    {
        $this->boot();
        AuthSession::set($data);
    }

    public function refresh(): void
    {
        $tokenKey = AuthSession::getTokenKey();
        if ($tokenKey) {
            \Pinoox\Component\Token::updateLifetime($tokenKey);
        }
    }
}

