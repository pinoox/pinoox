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

use Pinoox\Component\Router\Route;
use Pinoox\Component\Http\Request;
use Pinoox\Component\User;

class ManagerAuthFlow extends AuthFlow
{
    protected function before(Request $request): void
    {
        User::type(User::JWT);
        User::setUserSessionKey('manager_pinoox');
    }

    protected function exit(Request $request, Route $route)
    {
        return response()->json(['error' => 'Access denied!'], 401);
    }
}