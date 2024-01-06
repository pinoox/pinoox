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
use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Router\Route;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use pinoox\portal\Path;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function __invoke()
    {
        return View::render('hello');
    }
}
    
