<?php

namespace Pinoox\Component\User;

use Illuminate\Database\Eloquent\Builder;
use Pinoox\Portal\Hash;
use Pinoox\Model\UserModel;

class UserProvider
{
    /**
     * @param array<string, mixed> $credentials
     */
    public function retrieveByCredentials(array $credentials): ?UserModel
    {
        $login = $credentials['username']
            ?? $credentials['email']
            ?? $credentials['login']
            ?? null;

        if ($login === null || $login === '') {
            return null;
        }

        $activeOnly = (bool) ($credentials['active_only'] ?? true);

        return $this->retrieveByLogin((string) $login, $activeOnly);
    }

    public function validateCredentials(UserModel $user, string $password): bool
    {
        $user->makeVisible('password');

        return Hash::check($password, $user->password);
    }

    public function retrieveById(int $userId): ?UserModel
    {
        return UserModel::where('user_id', $userId)->first();
    }

    public function retrieveByLogin(string $identifier, bool $activeOnly = false): ?UserModel
    {
        $query = UserModel::where(function (Builder $builder) use ($identifier) {
            $builder->where('username', $identifier)->orWhere('email', $identifier);
        });

        if ($activeOnly) {
            $query->where('status', UserModel::ACTIVE);
        }

        return $query->first();
    }
}

