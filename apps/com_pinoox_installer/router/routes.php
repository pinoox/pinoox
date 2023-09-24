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
use pinoox\portal\Router;
use function pinoox\router\{route, collection, get};
use pinoox\app\com_pinoox_installer\controller\ApiController;

route(
    path: [
        '/',
        '/lang',
        '/setup',
        '/rules',
        '/prerequisites',
        'db'=>'/db'
    ],
    action: '@home',
    name:'aa',
    methods: 'GET'
);

route(
    path:'/user',
    action: function (){
        return new RedirectResponse(Router::path('db'));
    }
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
