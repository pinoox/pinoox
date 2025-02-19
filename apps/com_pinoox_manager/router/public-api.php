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

// auth
post(path: 'auth/login', action: [AuthController::class,'login']);
get(path: 'auth/logout', action: [AuthController::class, 'logout']);
get(path: 'auth/get', action: [AuthController::class, 'get']);