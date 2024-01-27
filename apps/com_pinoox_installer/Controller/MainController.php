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

namespace App\com_pinoox_installer\Controller;

use Pinoox\Component\Helpers\HelperHeader;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\App\App;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function home()
    {
        return View::render('index');
    }
}
    
