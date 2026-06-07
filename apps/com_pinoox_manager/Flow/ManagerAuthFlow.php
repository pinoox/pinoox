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

namespace App\com_pinoox_manager\Flow;

use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\Auth;

class ManagerAuthFlow extends AuthFlow
{
    protected function before(Request $request): void
    {
        Auth::boot();
    }

    protected function exit(Request $request, Route $route)
    {
        return ApiResponse::error('ACCESS_DENIED', 'Access denied!', status: 401, translate: false);
    }
}