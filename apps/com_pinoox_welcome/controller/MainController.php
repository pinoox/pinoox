<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\app\com_pinoox_welcome\controller;

use pinoox\component\kernel\controller\Controller;
use pinoox\component\Token;
use pinoox\model\UserModel;
use pinoox\portal\Env;
use pinoox\portal\Router;
use pinoox\portal\View;

class MainController extends Controller
{
    public function __invoke()
    {
        $routes = \pinoox\portal\app\AppEngine::routes('com_pinoox_installer','installeraaa');
        $routes->add('test',name:'test');
        dd(Router::path('route_1'));
        return View::render('hello');
    }
}
    
