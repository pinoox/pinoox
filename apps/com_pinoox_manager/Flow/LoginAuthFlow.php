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


use App\com_pinoox_shop\Flow\AuthFlow;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Http\Request;

class LoginAuthFlow extends AuthFlow
{
    protected function exit(Request $request, Route $route)
    {
        return response()->json(['error' => 'Access denied!'], 401);
    }
}