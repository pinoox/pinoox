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

use pinoox\component\http\RedirectResponse;
use function pinoox\router\{route, collection, get};
use pinoox\app\com_pinoox_installer\controller\ApiController;
use pinoox\component\helpers\Str;

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
    path: '/dist/pinoox.js',
    action: '@pinooxjs',
    methods: 'GET'
);

collection(
    path: '/api/v1',
    routes: 'router>api.php',
    controller: ApiController::class,
);
