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

use Pinoox\Component\Source\Portal;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Dispatcher extends Portal
{

    public static function __register(): void
    {
        self::__bind(EventDispatcher::class)
            ->addMethodCall('addSubscriber', [Listener::__ref('router')])
            ->addMethodCall('addSubscriber', [Listener::__ref('route')])
            ->addMethodCall('addSubscriber', [Listener::__ref('response')])
            ->addMethodCall('addSubscriber', [Listener::__ref('exception')])
            ->addMethodCall('addSubscriber', [Listener::__ref('controller')])
            ->addMethodCall('addSubscriber', [Listener::__ref('view')]);
    }

    /**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'kernel.dispatcher';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}
}
