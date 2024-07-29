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
        $name = $instance->eventName ?? $instance->name ?? null;
        return Event::dispatch($instance, $name);
    }
}