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

use App\com_pinoox_manager\Controller\AppViewController;
use Pinoox\Portal\View;
use function Pinoox\Router\{get, collection};

collection(path: '/api/v1', routes: __DIR__ . '/public-api.php');
collection(path: '/api/v1', routes: __DIR__ . '/private-api.php',
    flows: [
        'manager.auth',
    ],
);

get(
    path: 'app/{packageName}',
    action: [AppViewController::class, 'run'],
);

get(
    path: 'app/{packageName}/{subPath}',
    action: [AppViewController::class, 'run'],
    defaults: ['subPath' => ''],
    filters: ['subPath' => '.+'],
);

get(path: '/dist/pinoox.js', action: fn() => View::jsResponse('pinoox'));

get(
    path: '*',
    action: fn() => View::render('main'),
);