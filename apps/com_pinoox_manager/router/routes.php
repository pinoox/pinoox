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

use pinoox\component\Http\RedirectResponse;
use function pinoox\router\{route, collection, get};
use pinoox\app\com_pinoox_installer\controller\ApiController;
use pinoox\component\helpers\Str;

route(
    path: '/',
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
);
