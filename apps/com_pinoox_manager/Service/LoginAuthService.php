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


namespace App\com_pinoox_manager\Service;


use App\com_pinoox_shop\Service\AuthService;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Http\Request;

class LoginAuthService extends AuthService
{
    protected function exit(Request $request, Route $route)
    {
        return response()->json(['error' => 'Access denied!'], 401);
    }
}