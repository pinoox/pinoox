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

namespace pinoox\component\helpers;

use Closure;
use ReflectionFunction;

class HelperObject
{
    public static function closure_dump(Closure $c)
    {
        $str = 'function (';
        $r = new ReflectionFunction($c);
        $params = array();
        foreach ($r->getParameters() as $p) {
            $s = '';
            if ($p->isArray()) {
                $s .= 'array ';
            } else if ($p->getClass()) {
                $s .= $p->getClass()->name . ' ';
            }
            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
            }
            $params [] = $s;
        }
        $str .= implode(', ', $params);
        $str .= '){';
        $lines = file($r->getFileName());
        $start = $r->getStartLine() - 1;
        $function_text = '';
        for ($l = $start; $l < $r->getEndLine(); $l++) {
            $function_text .= $lines[$l];
        }
        $regex = '/function\s*?\(.*\)\s*?{(.*?)\}/ms';
        preg_match_all($regex, $function_text, $matches, PREG_SET_ORDER, 0);
        $function_text = isset($matches[0][1])?$matches[0][1] : null;
        return $str.$function_text.'}';
    }
}
    
