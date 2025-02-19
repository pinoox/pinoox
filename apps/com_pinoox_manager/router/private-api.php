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
use function Pinoox\Router\{get, post};

// auth
get(path: 'auth/logout', action: [AuthController::class, 'logout']);

// app
post(path: 'app/install', action: [AppController::class, 'install']);
get(path: 'app/getAll', action: [AppController::class, 'getAll']);