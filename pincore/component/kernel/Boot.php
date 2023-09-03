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

use pinoox\component\http\Request;
use pinoox\component\Lang;
use pinoox\component\store\Session;
use pinoox\component\Url;
use pinoox\portal\kernel\HttpKernel;
use Symfony\Component\HttpFoundation\Response;
use pinoox\portal\Router;
use pinoox\portal\app\App;
class Boot
{
    public static ?Request $request = null;
    public static Closure $next;

    public function build()
    {
        self::$request = Request::createFromGlobals();
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
      //  dd($router);
        Container::pincore()->removeDefinition('routes');
        Container::pincore()->set('routes', $router->routes);
    }

    public static function handle(?Request $request = null)
    {
        Lang::change(App::get('lang'));
        self::setRoute();
        $request = !empty($request) ? $request : self::$request;
        $response = HttpKernel::handle($request);
        $response->send();
        HttpKernel::terminate($request,$response);
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