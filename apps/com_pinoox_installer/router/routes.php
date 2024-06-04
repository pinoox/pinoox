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

use function Pinoox\Router\{route, collection, get};
use App\com_pinoox_installer\Controller\ApiController;

route(
    path: [
        '/',
        '/lang',
        '/setup',
        '/rules',
        '/prerequisites',
        '/db'
    ],
    action: '@home',
    methods: 'GET'
);

route(
    path:'/user',
    action: fn() => redirect('db'),
);

route(
    path: '/dist/pinoox.js',
    action: '@pinooxjs',
    methods: 'GET'
);

collection(
    path: '/api/v1',
    routes: 'router/api.php',
    controller: ApiController::class,
);