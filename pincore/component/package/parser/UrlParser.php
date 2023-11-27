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


use pinoox\component\Helpers\Str;
use pinoox\component\package\reference\UrlReference;
use pinoox\component\package\reference\ReferenceInterface;

class UrlParser implements ParserInterface
{
    public function __construct(private ?string $packageName = null)
    {
    }

    public function parse(ReferenceInterface|string $name): ReferenceInterface
    {
        if ($name instanceof ReferenceInterface) {
            return $name;
        }

        $parts = explode(':', $name);
        if (count($parts) > 1) {
            $app = $parts[0];
            $url = $parts[1];
        } else {
            $app = $this->packageName;
            $url = $parts[0];
            if (Str::firstHas($url, '~')) {
                $app = '~';
            }
        }

        $url = Str::firstDelete($url, '~');

        return new UrlReference($app, $url);
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }
}