<?php

namespace Pinoox\Portal;

use Pinoox\Component\Access\Manager;
use Pinoox\Component\Source\Portal;

/**
 * Authorization / permissions (RBAC + route abilities).
 *
 * @method static bool can(string|array $abilities, mixed $user = null, bool $requireAll = false)
 * @method static bool any(array $abilities, mixed $user = null)
 * @method static bool all(array $abilities, mixed $user = null)
 * @method static bool cannot(string|array $abilities, mixed $user = null)
 * @method static void authorize(string|array $abilities, mixed $user = null)
 * @method static void define(string $ability, \Closure $callback)
 * @method static list<string> abilitiesFor(\Pinoox\Model\UserModel $user)
 * @method static bool assignRole(int $userId, string $roleKey)
 * @method static bool givePermissionToRole(string $roleKey, string $permissionKey)
 * @method static string|null routePermission(?object $route, array $attributes = [])
 * @method static Manager ___()
 *
 * @see Manager
 */
class Access extends Portal
{
    public static function __register(): void
    {
        self::__bind(Manager::class);
    }

    public static function __name(): string
    {
        return 'access';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [
            'can',
            'any',
            'all',
            'cannot',
            'authorize',
            'define',
            'abilitiesFor',
            'assignRole',
            'givePermissionToRole',
            'routePermission',
        ];
    }
}

