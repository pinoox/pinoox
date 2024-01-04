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

use function Pinoox\Router\{route,get};

get(
    path: '/',
    action: '@welcome',
);

get(
    path: '/dist/pinoox.js',
    action: '@pinooxjs',
);



route(
    path: '/test/{path}',
    action: 'MainController:test',
    defaults: [
        'path' => ''
    ],
    filters: [
        'path' => '.*'
    ],
);