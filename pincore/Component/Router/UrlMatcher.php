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


namespace Pinoox\Component\Router;

use Symfony\Component\Routing\Matcher\UrlMatcher as BaseUrlMatcher;

class UrlMatcher extends BaseUrlMatcher
{
    public function match(string $pathinfo): array
    {
        $pathinfo = rtrim($pathinfo, '/'); // remove trailing slash
        return parent::match($pathinfo);
    }
}