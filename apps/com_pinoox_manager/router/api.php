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

use App\com_pinoox_manager\Controller\AuthController;
use function Pinoox\Router\{get, post};

post(
    path: '/user/login',
    action: [AuthController::class, 'login'],
);

get(
    path: '/user/get',
    action: [AuthController::class, 'getUser'],
);

get(
    path: '/user/getOptions',
    action: [AuthController::class, 'getOptions'],
);
