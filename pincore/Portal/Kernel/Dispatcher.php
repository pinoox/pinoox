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

/**
 * @method static object dispatch(object $event, ?string $eventName = NULL)
 * @method static array getListeners(?string $eventName = NULL)
 * @method static int|null getListenerPriority(string $eventName, array|callable $listener)
 * @method static bool hasListeners(?string $eventName = NULL)
 * @method static addListener(string $eventName, array|callable $listener, int $priority = 0)
 * @method static removeListener(string $eventName, array|callable $listener)
 * @method static addSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
 * @method static removeSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
 * @method static \Symfony\Component\EventDispatcher\EventDispatcher ___()
 *
 * @see \Symfony\Component\EventDispatcher\EventDispatcher
 */
class Dispatcher extends Portal
{
    public static function __register(): void
    {
        self::__bind(EventDispatcher::class)
            ->addMethodCall('addSubscriber', [Listener::__ref('request')])
            ->addMethodCall('addSubscriber', [Listener::__ref('router')])
            ->addMethodCall('addSubscriber', [Listener::__ref('routeEmpty')])
            ->addMethodCall('addSubscriber', [Listener::__ref('response')])
            ->addMethodCall('addSubscriber', [Listener::__ref('controller')])
            ->addMethodCall('addSubscriber', [Listener::__ref('exception')])
            ->addMethodCall('addSubscriber', [Listener::__ref('view')])
            ->addMethodCall('addSubscriber', [Listener::__ref('core_exception')]);
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
        return [];
    }
}
