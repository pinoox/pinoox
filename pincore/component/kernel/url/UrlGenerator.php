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


namespace pinoox\component\kernel\url;

use pinoox\portal\Router;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator as UrlGeneratorSymfony;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGenerator extends UrlGeneratorSymfony
{
    public function __construct(RequestContext $context, RouteCollection $routes = null, LoggerInterface $logger = null, string $defaultLocale = null)
    {
        $router = Router::getMainCollection();
        $routes = $router->routes;
        parent::__construct($routes, $context, $logger, $defaultLocale);
    }
}