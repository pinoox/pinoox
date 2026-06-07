<?php

use Pinoox\Portal\Access;

if (!function_exists('can')) {
    function can(string|array $abilities, mixed $user = null, bool $requireAll = false): bool
    {
        return Access::can($abilities, $user, $requireAll);
    }
}

if (!function_exists('cannot')) {
    function cannot(string|array $abilities, mixed $user = null): bool
    {
        return Access::cannot($abilities, $user);
    }
}

