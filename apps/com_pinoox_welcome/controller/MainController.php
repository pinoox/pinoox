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
use pinoox\portal\View;

class MainController extends Controller
{
    public function __invoke()
    {
        return View::render('hello');
    }
}
    
