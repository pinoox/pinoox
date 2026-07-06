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

use App\com_pinoox_manager\Controller\AuthController;
use App\com_pinoox_manager\Controller\OptionController;

return [
    [
        'method' => 'POST',
        'uri' => '/auth/login',
        'action' => [AuthController::class, 'login'],
        'name' => 'auth.login',
        'tag' => 'Authentication',
        'summary' => 'Login',
        'description' => 'Authenticate a manager user and start a session.',
    ],
    [
        'method' => 'GET',
        'uri' => '/auth/logout',
        'action' => [AuthController::class, 'logout'],
        'name' => 'auth.logout',
        'tag' => 'Authentication',
        'summary' => 'Logout',
        'description' => 'Destroy the current manager session.',
    ],
    [
        'method' => 'GET',
        'uri' => '/auth/get',
        'action' => [AuthController::class, 'get'],
        'name' => 'auth.get',
        'tag' => 'Authentication',
        'summary' => 'Get auth state',
        'description' => 'Returns the current authenticated user state.',
    ],
    [
        'method' => 'GET',
        'uri' => '/wallpapers/{name}',
        'action' => [OptionController::class, 'wallpaper'],
        'name' => 'wallpapers.show',
        'tag' => 'Assets',
        'summary' => 'Get wallpaper',
        'description' => 'Serve a manager wallpaper image by file name.',
        'params' => [
            ['name' => 'name', 'in' => 'path', 'type' => 'string', 'required' => true, 'description' => 'Wallpaper id (without extension)'],
        ],
        'filters' => [
            'name' => '[^/.]+',
        ],
    ],
];

