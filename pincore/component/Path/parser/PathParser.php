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


namespace pinoox\component\Path\parser;


use pinoox\component\Helpers\Str;
use pinoox\component\Path\reference\PathReference;
use pinoox\component\Path\reference\ReferenceInterface;

class PathParser implements ParserInterface
{
    public function parse(ReferenceInterface|string $name): ReferenceInterface
    {
        if ($name instanceof ReferenceInterface) {
            return $name;
        }

        $parts = explode(':', $name);
        if (count($parts) > 1) {
            $app = $parts[0];
            $path = $parts[1];
        } else {
            $app = null;
            $path = $parts[0];
            if (Str::firstHas($path, '~')) {
                $app = '~';
            }
        }

        $path = Str::firstDelete($path, '~');

        return new PathReference($app, $path);
    }
}