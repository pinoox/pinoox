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


namespace Pinoox\Component\Kernel\Url;

use Symfony\Component\Routing\Generator\UrlGenerator as UrlGeneratorSymfony;
use Symfony\Component\Routing\RouteCollection;

class UrlGenerator extends UrlGeneratorSymfony
{
    /**
     * @param RouteCollection $routes
     */
    public function setRoutes(RouteCollection $routes): void
    {
        $this->routes = $routes;
    }
}