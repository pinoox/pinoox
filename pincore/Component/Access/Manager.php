<?php

namespace Pinoox\Component\Access;

use Closure;
use Illuminate\Database\QueryException;
use Pinoox\Component\Validation\AuthorizationException;
use Pinoox\Portal\Auth;
use Pinoox\Model\PermissionModel;
use Pinoox\Model\RoleModel;
use Pinoox\Model\UserModel;

class Manager
{
    /** @var array<string, Closure> */

    private static array $gates = [];

    /** @var array<int, list<string>> */
    private array $abilityCache = [];

    public function can(string|array $abilities, mixed $user = null, bool $requireAll = false): bool
    {
        $config = AccessConfig::resolve();

        if (!$config['enabled']) {
            return true;
        }

        $abilities = is_array($abilities) ? $abilities : [$abilities];

        if ($abilities === []) {
            return true;
        }

        $user = $this->resolveUser($user);

        if ($user === null) {
            return false;
        }

        if ($this->isSuperUser($user, $config)) {
            return true;
        }

        $granted = $this->abilitiesFor($user);

        $check = fn (string $ability): bool => $this->checkAbility($ability, $user, $granted);

        if ($requireAll) {
            foreach ($abilities as $ability) {
                if (!$check($ability)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($abilities as $ability) {
            if ($check($ability)) {
                return true;
            }
        }

        return false;
    }

    public function any(array $abilities, mixed $user = null): bool
    {
        return $this->can($abilities, $user, false);
    }

    public function all(array $abilities, mixed $user = null): bool
    {
        return $this->can($abilities, $user, true);
    }

    public function cannot(string|array $abilities, mixed $user = null): bool
    {
        return !$this->can($abilities, $user);
    }

    public function authorize(string|array $abilities, mixed $user = null): void
    {
        if (!$this->can($abilities, $user)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    public function define(string $ability, Closure $callback): void
    {
        self::$gates[$ability] = $callback;
    }

    /**
     * @return list<string>
     */
    public function abilitiesFor(UserModel $user): array
    {
        if (isset($this->abilityCache[$user->user_id])) {
            return $this->abilityCache[$user->user_id];
        }

        $abilities = [];

        if (!empty($user->group_key)) {
            $abilities = array_merge($abilities, $this->groupAbilities((string) $user->group_key));
        }

        foreach ($this->roleKeysFor($user) as $roleKey) {
            $abilities = array_merge($abilities, $this->roleAbilities((string) $roleKey));
        }

        $abilities = array_values(array_unique(array_filter($abilities)));

        return $this->abilityCache[$user->user_id] = $abilities;
    }

    public function assignRole(int $userId, string $roleKey): bool
    {
        return $this->withRoles(function () use ($userId, $roleKey): bool {
            $role = RoleModel::where('role_key', $roleKey)->first();
            $user = UserModel::find($userId);

            if (!$role || !$user) {
                return false;
            }

            $user->roles()->syncWithoutDetaching([$role->role_id]);

            unset($this->abilityCache[$userId]);

            return true;
        }, false);
    }

    public function givePermissionToRole(string $roleKey, string $permissionKey): bool
    {
        return $this->withRoles(function () use ($roleKey, $permissionKey): bool {
            $role = RoleModel::where('role_key', $roleKey)->first();
            $permission = PermissionModel::firstOrCreate(
                ['permission_key' => $permissionKey],
                ['name' => $permissionKey],
            );

            if (!$role) {
                return false;
            }

            $role->permissions()->syncWithoutDetaching([$permission->permission_id]);

            return true;
        }, false);
    }

    public function routePermission(?object $route, array $attributes = []): ?string
    {
        if ($route !== null && method_exists($route, 'getData')) {
            $data = $route->getData();
            if (!empty($data['permission'])) {
                return (string) $data['permission'];
            }
        }

        foreach (['_api_permission', 'permission'] as $key) {
            if (!empty($attributes[$key])) {
                return (string) $attributes[$key];
            }
        }

        return null;
    }

    private function resolveUser(mixed $user): ?UserModel
    {
        if ($user instanceof UserModel) {
            return $user;
        }

        if (is_int($user)) {
            return UserModel::find($user);
        }

        return Auth::user();
    }

    /**
     * @param array{super_roles: list<string>, groups: array<string, list<string>>} $config
     */
    private function isSuperUser(UserModel $user, array $config): bool
    {
        if (!empty($user->group_key) && in_array($user->group_key, $config['super_roles'], true)) {
            return true;
        }

        if (!method_exists($user, 'roles')) {
            return false;
        }

        return $this->withRoles(function () use ($user, $config): bool {
            return $user->roles()->whereIn('role_key', $config['super_roles'])->exists();
        }, false);
    }

    /**
     * @return list<string>
     */
    private function roleKeysFor(UserModel $user): array
    {
        if (!method_exists($user, 'roles')) {
            return [];
        }

        return $this->withRoles(function () use ($user): array {
            return $user->roles()->pluck('role_key')->all();
        }, []);
    }

    private function withRoles(callable $callback, mixed $default): mixed
    {
        try {
            return $callback();
        } catch (QueryException) {
            return $default;
        }
    }

    /**
     * @param list<string> $granted
     */
    private function checkAbility(string $ability, UserModel $user, array $granted): bool
    {
        if (isset(self::$gates[$ability])) {
            return (bool) self::$gates[$ability]($user);
        }

        foreach ($granted as $item) {
            if ($this->matchesAbility($item, $ability)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAbility(string $granted, string $required): bool
    {
        if ($granted === '*' || $granted === $required) {
            return true;
        }

        if (str_ends_with($granted, '.*')) {
            $prefix = substr($granted, 0, -2);

            return str_starts_with($required, $prefix . '.');
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function groupAbilities(string $groupKey): array
    {
        $groups = AccessConfig::resolve()['groups'];

        return array_values($groups[$groupKey] ?? []);
    }

    /**
     * @return list<string>
     */
    private function roleAbilities(string $roleKey): array
    {
        return $this->withRoles(function () use ($roleKey): array {
            $role = RoleModel::with('permissions')->where('role_key', $roleKey)->first();

            if (!$role) {
                return [];
            }

            return $role->permissions->pluck('permission_key')->all();
        }, []);
    }
}

