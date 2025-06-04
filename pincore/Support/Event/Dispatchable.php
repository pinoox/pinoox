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


namespace Pinoox\Support\Event;

use Pinoox\Portal\Event;

trait Dispatchable
{
    public static function dispatch(...$arguments): object
    {
        $instance = new static(...$arguments);
        return Event::dispatch($instance, static::$eventName);
    }

    public static function subDispatch(string $subname, ...$arguments): object
    {
        $instance = new static(...$arguments);
        $eventName = self::subname($subname);
        return Event::dispatch($instance, $eventName);
    }

    public static function subFreeDispatch(string $eventName, ...$arguments): object
    {
        $instance = new static(...$arguments);
        return Event::dispatch($instance, $eventName);
    }

    public static function subname(string $subname): string
    {
        $eventName = static::$eventName;
        $eventName .= '.' . $subname;
        return $eventName;
    }
}