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

namespace pinoox\portal\kernel;

use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as ObjectPortal1;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use pinoox\component\Helpers\Str;
use pinoox\component\kernel\Container;
use pinoox\component\kernel\ContainerBuilder;
use pinoox\component\kernel\Kernel;
use pinoox\component\kernel\event\ResponseEvent;
use pinoox\component\source\Portal;
use pinoox\portal\Path;
use pinoox\portal\Router;

/**
 * @method static ObjectPortal1 handleSubRequest(\Symfony\Component\HttpFoundation\Request $request)
 * @method static ObjectPortal1 handle(\Symfony\Component\HttpFoundation\Request $request, int $type = 1, bool $catch = true)
 * @method static terminate(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response)
 * @method static HttpKernel terminateWithException(\Throwable $exception, ?Symfony\Component\HttpFoundation\Request $request = NULL)
 * @method static \Symfony\Component\HttpFoundation\RequestStack ___requestStack()
 * @method static \Symfony\Component\Routing\RequestContext ___context()
 * @method static \Symfony\Component\Routing\Matcher\UrlMatcher ___matcher()
 * @method static \Symfony\Component\Routing\Generator\UrlGenerator ___urlGenerator()
 * @method static \pinoox\component\kernel\Kernel ___()
 *
 * @see \pinoox\component\kernel\Kernel
 */
class HttpKernel extends Portal
{
	public static function __register(): void
	{
		$routes = Router::getMainCollection()->routes;
		self::setParams();
		self::addEvents();
		self::__bind(RequestStack::class, 'request_stack');

		self::__bind(RequestContext::class, 'context');

		self::__bind(UrlMatcher::class, 'matcher')
		    ->setArguments([$routes, self::__ref('context')]);

		self::__bind(UrlGenerator::class, 'url_generator')
		    ->setArguments([$routes, self::__ref('context')]);

		self::__bind(Kernel::class)
		    ->setArguments([
		        Dispatcher::__ref(),
		        Resolver::__ref('controller'),
		        self::__ref('request_stack'),
		        Resolver::__ref('argument'),
		    ]);
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
