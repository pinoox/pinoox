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

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\event\EventDispatcher;
use Pinoox\Portal\App\App;

/**
 * @method static Event listen(string $eventName, array|callable $listener, int $priority = 0)
 * @method static object dispatch(object $event, ?string $eventName = NULL)
 * @method static array getListeners(?string $eventName = NULL)
 * @method static int|null getListenerPriority(string $eventName, array|callable $listener)
 * @method static bool hasListeners(?string $eventName = NULL)
 * @method static addListener(string $eventName, array|callable $listener, int $priority = 0)
 * @method static removeListener(string $eventName, array|callable $listener)
 * @method static addSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
 * @method static removeSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
 * @method static \Pinoox\Component\event\EventDispatcher ___()
 *
 * @see \Pinoox\Component\event\EventDispatcher
 */
class Event extends Portal
{
    public static function __register(): void
    {
        self::__bind(EventDispatcher::class);
    }

    public static function __app(): string
    {
        return App::package();
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'event';
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
            'listen'
        ];
    }
}
