<?php

namespace App\com_pinoox_app\Router;

/**
 * Named route action identifiers for this app.
 *
 * Use with routes:  get('/')->actionName(Actions::HOME)
 * Register in:      routes/actions.php via action(Actions::HOME, ...)
 */
final class Actions
{
    public const HOME = 'home';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::HOME,
        ];
    }
}
