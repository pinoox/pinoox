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

use function Pinoox\Router\{get, collection};

get(
    path: '*',
    action: '@home',
);

get(
    path: '/dist/pinoox.js',
    action: '@pinooxjs',
);

collection(
    path: '/api/v1',
    routes: 'router/api.php',
);
