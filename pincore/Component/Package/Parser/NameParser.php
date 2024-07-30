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


namespace Pinoox\Component\Package\Parser;


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Package\Reference\NameReference;
use Pinoox\Component\Package\Reference\ReferenceInterface;

class NameParser implements ParserInterface
{
    public function parse(ReferenceInterface|string $name): ReferenceInterface
    {
        if ($name instanceof ReferenceInterface) {
            return $name;
        }

        $parts = explode(':', $name);
        if (count($parts) > 1) {
            $app = $parts[0];
            $value = $parts[1];
        } else {
            $app = null;
            $value = $parts[0];
            if (Str::firstHas($value, '~')) {
                $app = '~';
            }
        }

        $value = Str::firstDelete($value, '~');

        return new NameReference($app, $value);
    }
}