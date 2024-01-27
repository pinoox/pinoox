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
        'type' => 'multiple',
        'routes' => [
            'router/routes.php',
            'router/actions.php',
        ],
    ],
    'domain' => true,
    'auto-null' => true,
    'global-data' => true,
    'prefix-data' => 'pin_',
    'main-controller' => 'main',
    'main-method' => '_main',
    'exception-method' => '_exception',
    'rewrite' => [],
    'rewrite-filter' => [],
    'service' => [],
    'loader' => [],
    'startup' => null,
    'session' => null,
    'token' => null,
    'user-type' => null,
    'user' => null,
    'lang' => 'en',
    'theme' => 'default',
    'path-theme' => 'theme',
    'name' => 'app',
    'description' => 'pinoox app',
    'icon' => null,
    'version-name' => '1.0',
    'version-code' => 1,
    'developer' => 'pinoox developer',
    'min-pin' => 0,
    'sys-app' => false,
    'dock' => true,
    'db' => [
        ''
    ],
];