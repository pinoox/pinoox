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

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function home()
    {
        Container::Illuminate()->bind(HashManager::class,function (\illuminate\Container\Container $app){
            return new HashManager($app);
        });

        Container::Illuminate()->get(HashManager::class);
        \Pinoox\Portal\Hash::make('sdsd');
       dd( Hash::make('sss'));
        return View::render('index');
    }
}
    
