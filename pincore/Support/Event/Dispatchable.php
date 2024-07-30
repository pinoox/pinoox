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
        $eventName = static::$eventName;
        $eventName .= '.' . $subname;
        return Event::dispatch($instance, $eventName);
    }
}