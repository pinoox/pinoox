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

namespace Pinoox\Component\Upload;

use ReflectionClass;

enum Event
{
    case CreateThumb;

    case Insert;

    case Delete;

    public static function getName(Event $event): string
    {
        $reflection = new ReflectionClass(Event::class);
        $constants = $reflection->getConstants();

        return array_search($event, $constants);
    }
}

