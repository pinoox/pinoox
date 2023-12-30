<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal\Kernel;

use pinoox\component\package\AppLayer;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as ObjectPortal1;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Kernel\Event\ResponseEvent;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\Path;
use Pinoox\Portal\Router;

/**
 * @method static ObjectPortal1 handleSubRequest(\Symfony\Component\HttpFoundation\Request $request)
 * @method static ObjectPortal1 handle(\Symfony\Component\HttpFoundation\Request $request, int $type = 1, bool $catch = true)
 * @method static terminate(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response)
 * @method static HttpKernel terminateWithException(\Throwable $exception, ?Symfony\Component\HttpFoundation\Request $request = NULL)
 * @method static \Symfony\Component\HttpFoundation\RequestStack ___requestStack()
 * @method static \Symfony\Component\Routing\RequestContext ___context()
 * @method static \Symfony\Component\Routing\Matcher\UrlMatcher ___matcher()
 * @method static \Symfony\Component\Routing\Generator\UrlGenerator ___urlGenerator()
 * @method static \Pinoox\Component\Kernel\Kernel ___()
 *
 * @see \Pinoox\Component\Kernel\Kernel
 */
class HttpKernel extends Portal
{
    public static function __register(): void
    {
        self::setParams();
        self::addEvents();
        self::__bind(RequestStack::class, 'request_stack');

        self::__bind(RequestContext::class, 'context');

        self::__bind(UrlMatcher::class, 'matcher')
            ->setArguments([self::__ref('routes'), self::__ref('context')]);

        self::__bind(UrlGenerator::class, 'url_generator')
            ->setArguments([self::__ref('routes'), self::__ref('context')]);

        self::__bind(Kernel::class)
            ->setArguments([
                Dispatcher::__ref(),
                Resolver::__ref('controller'),
                self::__ref('request_stack'),
                Resolver::__ref('argument'),
            ]);

        self::setRouteDefault();
    }

    public static function setRouts(string $package, string $path): void
    {
        $router = AppEngine::router($package, $path);
        self::__bind($router->getCollection()->routes, 'routes');
    }

    public static function setRouteDefault(): void
    {
        $layer = AppRouter::find();
        self::setRouts($layer->getPackageName(), $layer->getPath());
    }


    private static function setParams(): void
    {
        self::__param('charset', 'UTF-8');
    }


    private static function addEvents(): void
    {
        self::__container()->addCompilerPass(new AddEventAliasesPass(
            [
                ResponseEvent::class => 'response',
            ]
        ));
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel';
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'terminateWithException'
        ];
    }
}
