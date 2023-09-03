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


namespace pinoox\component\package\parser;


use pinoox\component\helpers\Str;
use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;

class PathParser implements ParserInterface
{
    public function __construct(private ?string $packageName = null)
    {
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

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
            $app = $this->packageName;
            $path = $parts[0];
            if (Str::firstHas($path, '~')) {
                $app = '~';
            }
        }

        $path = Str::firstDelete($path, '~');

        return new PathReference($app, $path);
    }
}