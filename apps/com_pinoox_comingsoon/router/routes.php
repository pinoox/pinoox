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

use App\com_pinoox_comingsoon\Controller\MainController;
use function Pinoox\Router\{get,post};

get(
    path: '/',
    action: [MainController::class,'main'],
);

get(
    path: '/panel',
    action: [MainController::class,'panel'],
);

post(
    path: '/panel',
    action: [MainController::class,'save'],
);

get(
    path: '*',
    action: fn() => redirect(url('/')),
);