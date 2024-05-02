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
        $str = 'function (';
        $reflection = new ReflectionFunction($closure);
        $params = [];

        foreach ($reflection->getParameters() as $param) {
            $paramStr = '';

            $paramType = $param->getType();
            if ($paramType !== null && $paramType->getName() === 'array') {
                $paramStr .= 'array ';
            } elseif ($paramType !== null) {
                $paramStr .= $paramType->getName() . ' ';
            }

            if ($param->isPassedByReference()) {
                $paramStr .= '&';
            }

            $paramStr .= '$' . $param->name;

            if ($param->isOptional()) {
                $paramStr .= ' = ' . var_export($param->getDefaultValue(), true);
            }

            $params[] = $paramStr;
        }

        $str .= implode(', ', $params) . '){';

        $lines = file($reflection->getFileName());
        $start = $reflection->getStartLine() - 1;
        $end = $reflection->getEndLine();

        $functionText = '';

        for ($line = $start; $line < $end; $line++) {
            $functionText .= $lines[$line];
        }

        $regex = '/function\s*?\(.*\)\s*?{(.*?)\}/ms';
        preg_match_all($regex, $functionText, $matches, PREG_SET_ORDER, 0);
        $functionText = $matches[0][1] ?? null;

        return $str . $functionText . '}';
    }



}
    
