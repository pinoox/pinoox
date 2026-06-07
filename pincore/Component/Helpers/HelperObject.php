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

namespace Pinoox\Component\Helpers;

use Closure;
use ReflectionFunction;

class HelperObject
{
    public static function closure_dump(Closure $closure): string
    {
        $reflection = new ReflectionFunction($closure);
        $lines = file($reflection->getFileName());
        $start = $reflection->getStartLine() - 1;
        $end = $reflection->getEndLine();

        $source = '';

        for ($line = $start; $line < $end; $line++) {
            $source .= $lines[$line];
        }

        $functionPos = strpos($source, 'function');

        if ($functionPos === false) {
            return 'function () {}';
        }

        $source = substr($source, $functionPos);
        $bracePos = strpos($source, '{');

        if ($bracePos === false) {
            return trim($source);
        }

        $length = strlen($source);
        $depth = 0;
        $quote = null;
        $escape = false;

        for ($i = $bracePos; $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== null) {
                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\') {
                    $escape = true;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return trim(substr($source, 0, $i + 1));
                }
            }
        }

        return trim($source);
    }

}
    
