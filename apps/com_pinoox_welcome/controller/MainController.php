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

namespace App\com_pinoox_welcome\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\AppManager;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Router\RouteCapsule;
use Pinoox\Component\Token;
use Pinoox\Model\UserModel;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Env;
use Pinoox\Portal\FileSystem;
use Pinoox\Portal\Kernel\HttpKernel;
use Pinoox\Portal\Path;
use Pinoox\Portal\Request;
use Pinoox\Portal\Router;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function __invoke(Route $route)
    {
        return View::render('hello');
    }
}
    
