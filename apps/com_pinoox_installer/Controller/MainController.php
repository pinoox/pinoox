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


use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\Url;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function home()
    {
        return View::render('index');
    }

    public function pinooxjs(Request $request)
    {
        return View::jsResponse('pinoox');
    }
}

