<?php

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\User\LoginResult;
use Pinoox\Component\User\Manager;
use Pinoox\Model\UserModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * User authentication and profile management.
 *
 * Same service as {@see Auth} — use whichever name fits your code style.
 *
 * @method static void boot()
 * @method static bool attempt(array $credentials, bool $remember = false)
 * @method static LoginResult attemptResult(array $credentials, bool $remember = false)
 * @method static bool check()
 * @method static bool guest()
 * @method static bool loggedIn()
 * @method static string|null login(UserModel $user, bool $remember = false)
 * @method static bool loginUsingId(int $userId, bool $remember = false)
 * @method static void logout()
 * @method static UserModel|null user()
 * @method static int|null id()
 * @method static string|null token()
 * @method static void reset()
 * @method static mixed get(?string $field = null)
 * @method static UserModel|null record(?int $userId = null)
 * @method static mixed session(?string $field = null)
 * @method static mixed sessionData(?string $field = null)
 * @method static void sessionPut(array|string $key, mixed $value = null)
 * @method static void sessionReplace(array $data)
 * @method static void refresh()
 * @method static bool locked()
 * @method static array lock()
 * @method static bool|string unlock(string $password)
 * @method static array profile()
 * @method static bool updateProfile(int $userId, array $data)
 * @method static bool|string changePassword(int $userId, string $oldPassword, string $newPassword)
 * @method static array|false changeAvatar(int $userId, UploadedFile $file)
 * @method static array|null removeAvatar(int $userId)
 * @method static list<array<string, mixed>> listForApp(string $package)
 * @method static UserModel|null find(int $userId)
 * @method static UserModel|null findByLogin(string $identifier, bool $activeOnly = false)
 * @method static UserModel create(array $data)
 * @method static bool setStatus(int $userId, string $status)
 * @method static bool remove(int $userId)
 * @method static int revokeSessions(int $userId)
 * @method static mixed meta(?string $key = null)
 * @method static bool setMeta(array|string $key, mixed $value = null)
 * @method static array profileRules(int $userId)
 * @method static array passwordRules()
 * @method static Manager ___()
 *
 * @see Manager
 */
class User extends Portal
{
    public static function __register(): void
    {
        self::__bind(Manager::class);
    }

    public static function __name(): string
    {
        return 'user';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [];
    }
}

