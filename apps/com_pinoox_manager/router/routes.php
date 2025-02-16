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

use App\com_pinoox_manager\Flow\LoginAuthFlow;
use Pinoox\Portal\View;
use function Pinoox\Router\{get, collection};

get(
    path: '*',
    action: fn() => View::render('main'),
);

get(
    path: '/dist/pinoox.js',
    action: fn() => View::jsResponse('pinoox')
);

collection(
    path: '/api/v1',
    routes: __DIR__ . '/api.php',
);

collection(
    path: '/api/v1',
    routes: __DIR__ . '/api-auth.php',
    flows: [
        LoginAuthFlow::class
    ]
);
