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
        dd(Token::get('26e0041b6a5bd952edd689dd20d7d923')->token_data);
        return View::render('hello');
    }
}
    
