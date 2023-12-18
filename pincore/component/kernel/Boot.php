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

namespace pinoox\component\kernel;

use pinoox\component\Helpers\HelperString;
use pinoox\component\Http\Request;
use pinoox\component\Lang;
use pinoox\component\store\Session;
use pinoox\component\Url;
use pinoox\portal\Config;
use pinoox\portal\kernel\HttpKernel;
use pinoox\portal\Path;
use Symfony\Component\HttpFoundation\Response;
use pinoox\portal\Router;
use pinoox\portal\app\App;

class Boot
{
    public static ?Request $request = null;
    public static Closure $next;

    public function build()
    {
        self::$request = Request::take();
        if (is_null(Url::request())) {
            /* global $argv;
             Console::run($argv);*/
            (new Terminal())->run();
        } else {
            Boot::handle();
            // App::run();
        }
        $core = Container::pincore();
        return;
        $this->buildContainer($core);


    }

    private function buildContainer(ContainerBuilder $container): void
    {
        $this->setNext();
    }

    private static function setRoute()
    {
        $router = Router::getMainCollection();
    }

    public static function handle(?Request $request = null)
    {
        Lang::change(App::get('lang'));
        self::loadLoader();
        self::setRoute();
        $request = !empty($request) ? $request : self::$request;
        $response = HttpKernel::handle($request);
        $response->send();
        HttpKernel::terminate($request, $response);
    }

    private static function loadLoader(): void
    {
        $coreLoaders = Config::name('~loader')->get();
        $appLoaders = App::get('loader');
        $loaders = array_merge($appLoaders, $coreLoaders);
        $classMap = [];
        foreach ($loaders as $classname => $path) {
            if (HelperString::firstHas($classname, '@')) {
                require_once Path::get($path);
            } else {
                $classMap[$classname] = $path;
            }
        }

        Loader::composer()->addClassMap($classMap);
    }

    private function setNext(): void
    {
        self::$request->setSession(new Session());
        self::$next = function ($request): Response {
            return HttpKernel::handle($request);
        };
        //  $container->get('dispatcher')->dispatch(new ResponseEvent($response, self::$request), 'response');
    }


}