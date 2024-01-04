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

namespace Pinoox\Component\Kernel;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Lang;
use pinoox\component\package\AppLayer;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\App\AppRouter;

class Boot
{
    public static Closure $next;

    public function build()
    {
        if (empty(AppRouter::getRequest()->getHost())) {
            (new Terminal())->run();
        } else {
            Boot::handle();
        }
    }

    public static function handle(?Request $request = null)
    {
        Lang::change(App::get('lang'));
        AppProvider::run();
    }
}