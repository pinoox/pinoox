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

use App\com_pinoox_manager\Controller\AppController;

use App\com_pinoox_manager\Controller\AuthController;
use App\com_pinoox_manager\Controller\RouterController;
use function Pinoox\Router\{get, post};

// auth
get(path: 'auth/logout', action: [AuthController::class, 'logout']);

get(
    path: '/user/get',
    action: [AuthController::class, 'getUser'],
);

get(
    path: '/user/getOptions',
    action: [AuthController::class, 'getOptions'],
);

post(
    path: 'app/install',
    action: [AppController::class, 'install']
);

get(
    path: 'app/getAll',
    action: [AppController::class, 'getAll']
);

get(
    path: 'router/getAll',
    action: [RouterController::class, 'getAll']
);

post(
    path: 'router/add',
    action: [RouterController::class, 'add']
);

post(
    path: 'router/remove',
    action: [RouterController::class, 'remove']
);

post(
    path: 'router/setPackageName',
    action: [RouterController::class, 'setPackageName']
);
