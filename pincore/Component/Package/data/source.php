<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

return [
    'enable' => true,
    'hidden' => false,
    'package' => 'com_pinoox_welcome',
    'open' => 'app-details',
    'router' => [
        'routes' => [
            'routes/web.php',
            'routes/actions.php',
        ],
    ],
    'domain' => true,
    'flow' => [],
    'alias' => [
        'permission' => \Pinoox\Flow\PermissionFlow::class,
    ],
    'loader' => [],
    'startup' => null,
    'boot' => true,
    'boot-global' => false,
    'runtime' => [
        'mode' => null,
        'debug' => null,
    ],
    'extends' => [],
    'depends' => [],
    'session' => null,
    'token' => null,
    'auth' => [
        'mode' => 'cookie',
        'lifetime' => 30,
        'lifetime_unit' => 'day',
    ],
    // Shared resources between apps. Values: local | platform | host | {package}
    'transport' => [
        'user' => null,
        'auth' => null,
        'token' => null,
        'file' => null,
        'access' => null,
    ],
    'filesystem' => [
        'disk' => null,
        'default_access' => 'public',
        'thumb_width' => 512,
        'thumb_height' => 512,
    ],
    'log' => [
        'level' => null,
        'path' => null,
        'rotate' => null,
        'max_files' => null,
    ],
    'access' => [
        'enabled' => true,
        'super_roles' => ['admin', 'superadmin'],
        'groups' => [],
    ],
    'cache' => [
        'enabled' => false,
        'mode' => null,
        'stores' => [
            'routes' => true,
            'api' => true,
            'boot' => true,
            'twig' => true,
            'graphql' => true,
            'pinker' => true,
        ],
        'twig' => [],
        'build' => [
            'include_in_package' => true,
            // 'stores' => ['pinker' => true], // optional override for cache:build / install
        ],
    ],
    'redis' => [
        'prefix' => null,
    ],
    'date' => [
        'calendar' => null,
        'timezone' => null,
    ],
    'container' => [
        'enabled' => false,
        'autowire_controllers' => true,
        'bindings' => [],
        'singletons' => [],
    ],
    'user' => null,
    'database' => null,
    'table' => [
        'prefix' => null,
    ],
    'lang' => 'en',
    'theme' => 'default',
    'theme-context' => null,
    'theme-contexts' => [],
    'theme-extends' => null,
    'path-theme' => 'theme',
    'frontend' => [
        'stack' => null,
        'entry' => null,
        'manifest' => null,
        'seo' => [
            'defaults' => [],
        ],
    ],
    'name' => 'app',
    'description' => 'pinoox app',
    'icon' => null,
    'version-name' => '1.0',
    'version-code' => 1,
    'developer' => 'pinoox developer',
    'minpin' => 0,
    'sys-app' => false,
    'dock' => true,
    'pinx' => [
        'type' => 'app',
        'target_app' => null,
        'theme_name' => null,
        'minpin' => 0,
        'sign' => [
            'enabled' => false,
            'key' => null,
            'key_id' => null,
            'require' => false,
        ],
    ],
    'build' => [
        'gitignore' => true,
        'exclude' => [],
        'include_themes' => [],
    ],
];

