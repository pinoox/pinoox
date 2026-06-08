<?php

namespace Pinoox\Component\User;

use Pinoox\Portal\Date;
use Pinoox\Model\FileModel;
use Pinoox\Model\TokenModel;
use Pinoox\Model\UserModel;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\File;
use Pinoox\Portal\Hash;
use Pinoox\Portal\Url;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Manager
{
    private readonly Guard $guard;
    private readonly UserProvider $provider;

    public function __construct(?Guard $guard = null, ?UserProvider $provider = null)
    {
        $this->provider = $provider ?? new UserProvider();
        $this->guard = $guard ?? new Guard($this->provider);
    }

    // ── Authentication ──────────────────────────────────────────────────

    public function boot(): void
    {
        $this->guard->boot();
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        return $this->guard->attempt($credentials, $remember)->success;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function attemptResult(array $credentials, bool $remember = false): LoginResult
    {
        return $this->guard->attemptResult($credentials, $remember);
    }

    public function check(): bool
    {
        return $this->guard->check();
    }

    public function guest(): bool
    {
        return $this->guard->guest();
    }

    public function loggedIn(): bool
    {
        return $this->check();
    }

    public function login(UserModel $user, bool $remember = false): ?string
    {
        return $this->guard->login($user, $remember);
    }

    public function loginUsingId(int $userId, bool $remember = false): bool
    {
        return $this->guard->loginUsingId($userId, $remember);
    }

    public function logout(): void
    {
        $this->guard->logout();
    }

    public function user(): ?UserModel
    {
        return $this->guard->user();
    }

    public function id(): ?int
    {
        return $this->guard->id();
    }

    public function token(): ?string
    {
        return $this->guard->token();
    }

    public function reset(): void
    {
        $this->guard->reset();
    }

    public function setRequestToken(?string $token): void
    {
        $this->guard->setRequestToken($token);
    }

    public function persistClientJwt(string $jwt): void
    {
        $this->guard->persistClientJwt($jwt);
    }

    // ── Session helpers ─────────────────────────────────────────────────

    public function get(?string $field = null): mixed
    {
        return $this->guard->get($field);
    }

    public function record(?int $userId = null): ?UserModel
    {
        return $userId !== null
            ? $this->provider->retrieveById($userId)
            : $this->user();
    }

    public function session(?string $field = null): mixed
    {
        return $this->guard->session($field);
    }

    public function sessionData(?string $field = null): mixed
    {
        return $this->guard->sessionData($field);
    }

    public function sessionPut(array|string $key, mixed $value = null): void
    {
        $this->guard->sessionPut($key, $value);
    }

    public function sessionReplace(array $data): void
    {
        $this->guard->sessionReplace($data);
    }

    public function refresh(): void
    {
        $this->guard->refresh();
    }

    // ── Screen lock ─────────────────────────────────────────────────────

    public function locked(): bool
    {
        return (bool) $this->sessionData('isLock');
    }

    public function lock(): array
    {
        if ($this->check()) {
            $this->sessionPut('isLock', true);
        }

        return $this->profile();
    }

    public function unlock(string $password): bool|string
    {
        if (!$this->check()) {
            return t('user.you_must_login');
        }

        $user = $this->user();
        $user?->makeVisible('password');

        if (!$user || !Hash::check($password, $user->password)) {
            return t('user.password_is_wrong');
        }

        $this->sessionPut('isLock', false);

        return true;
    }

    // ── Profile ─────────────────────────────────────────────────────────

    public function profile(): array
    {
        $user = $this->user();
        if (!$user) {
            return [];
        }

        $avatar = $this->avatar($user);

        if ($this->locked()) {
            return [
                'isLock' => true,
                'full_name' => $user->full_name,
                'avatar' => $avatar['file_link'],
                'avatar_thumb' => $avatar['thumb_link'],
                'isAvatar' => $avatar['file_id'] !== null,
            ];
        }

        return [
            'avatar' => $avatar['file_link'],
            'avatar_thumb' => $avatar['thumb_link'],
            'isAvatar' => $avatar['file_id'] !== null,
            'fname' => $user->fname,
            'lname' => $user->lname,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'status' => $user->status,
            'group_key' => $user->group_key,
            'metadata' => $user->metadata ?? [],
        ];
    }

    public function updateProfile(int $userId, array $data): bool
    {
        return (bool) UserModel::where('user_id', $userId)->update([
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'username' => $data['username'],
        ]);
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool|string
    {
        $user = UserModel::where('user_id', $userId)->first();
        $user?->makeVisible('password');

        if (!$user || !Hash::check($oldPassword, $user->password)) {
            return t('user.err_old_password');
        }

        UserModel::updatePassword($userId, $newPassword);

        return true;
    }

    public function changeAvatar(int $userId, UploadedFile $file): array|false
    {
        $oldAvatarId = UserModel::where('user_id', $userId)->value('avatar_id');

        $result = File::upload($file)
            ->to('uploads/avatar')
            ->group('avatar')
            ->thumb()
            ->save();

        if (!$result->success || $result->id === null) {
            return false;
        }

        UserModel::where('user_id', $userId)->update(['avatar_id' => $result->id]);

        if (!empty($oldAvatarId)) {
            File::remove((int) $oldAvatarId);
        }

        return [
            'avatar' => $result->url,
            'avatar_thumb' => $result->thumb,
        ];
    }

    public function removeAvatar(int $userId): ?array
    {
        $user = UserModel::where('user_id', $userId)->first();
        if (!$user) {
            return null;
        }

        DB::beginTransaction();

        try {
            if ($user->avatar_id) {
                File::remove((int) $user->avatar_id);
            }

            UserModel::where('user_id', $userId)->update(['avatar_id' => null]);
            DB::commit();
        } catch (\Throwable) {
            DB::rollBack();

            return null;
        }

        return $this->profile();
    }

    // ── Admin / multi-app ───────────────────────────────────────────────

    /**
     * @return list<array<string, mixed>>
     */
    public function listForApp(string $package): array
    {
        UserModel::setPackage($package);

        return UserModel::with('file')->get()->map(function (UserModel $user) {
            $avatar = $this->avatar($user);

            return [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'app' => $user->app,
                'register_date' => $user->created_at?->format('Y-m-d H:i:s'),
                'register_date_fa' => Date::jalali($user->created_at)->format('Y/m/d'),
                'fname' => $user->fname,
                'lname' => $user->lname,
                'username' => $user->username,
                'status' => $user->status,
                'status_fa' => t('user.' . $user->status),
                'full_name' => $user->full_name,
                'avatar' => $avatar['file_link'],
                'avatar_thumb' => $avatar['thumb_link'],
            ];
        })->all();
    }

    public function find(int $userId): ?UserModel
    {
        return $this->provider->retrieveById($userId);
    }

    public function findByLogin(string $identifier, bool $activeOnly = false): ?UserModel
    {
        return $this->provider->retrieveByLogin($identifier, $activeOnly);
    }

    public function create(array $data): UserModel
    {
        return UserModel::create($data);
    }

    public function setStatus(int $userId, string $status): bool
    {
        if (!in_array($status, [UserModel::ACTIVE, UserModel::INACTIVE, UserModel::SUSPEND, UserModel::PENDING], true)) {
            return false;
        }

        return (bool) UserModel::where('user_id', $userId)->update(['status' => $status]);
    }

    public function remove(int $userId): bool
    {
        $user = UserModel::where('user_id', $userId)->first();

        return (bool) $user?->delete();
    }

    public function revokeSessions(int $userId): int
    {
        return TokenModel::where('user_id', $userId)->delete();
    }

    public function meta(?string $key = null): mixed
    {
        $user = $this->user();
        $metadata = $user?->metadata ?? [];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    public function setMeta(array|string $key, mixed $value = null): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $metadata = $user->metadata ?? [];

        if (is_array($key)) {
            $metadata = array_merge($metadata, $key);
        } else {
            $metadata[$key] = $value;
        }

        return (bool) UserModel::where('user_id', $user->user_id)->update(['metadata' => $metadata]);
    }

    /**
     * @return array<string, mixed>
     */
    public function profileRules(int $userId): array
    {
        return [
            'fname' => 'required|min:3',
            'lname' => 'required|min:3',
            'email' => ['required', 'email', UserModel::ruleUnique('email', $userId)],
            'username' => ['required', 'alpha_dash:ascii', 'min:3', UserModel::ruleUnique('username', $userId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function passwordRules(): array
    {
        return [
            'old_password' => 'required',
            'new_password' => 'required|min:5|different:old_password',
            'valid_password' => 'required|same:new_password',
        ];
    }

    /**
     * @return array{file_id: int|null, file_link: string, thumb_link: string}
     */
    private function avatar(UserModel $user): array
    {
        $default = Url::asset('resources/avatar.png');

        if ($user->file) {
            return [
                'file_id' => $user->avatar_id,
                'file_link' => Url::check($user->file->file_link, $default),
                'thumb_link' => Url::check($user->file->thumb_link, $default),
            ];
        }

        return [
            'file_id' => null,
            'file_link' => $default,
            'thumb_link' => $default,
        ];
    }
}

