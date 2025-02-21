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

use Pinoox\Portal\View;
use function Pinoox\Router\{get, collection};

collection(path: '/api/v1', routes: __DIR__ . '/public-api.php');
collection(path: '/api/v1', routes: __DIR__ . '/private-api.php',
    flows: [
        'manager.auth',
    ],
);

get(
    path: '*',
    action: fn() => View::render('main'),
);
get(path: '/dist/pinoox.js', action: fn() => View::jsResponse('pinoox'));