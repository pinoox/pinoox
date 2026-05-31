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

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Router\QueryRouteResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class QueryRouteListener implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request instanceof Request || !$request->isQueryRoute()) {
            return;
        }

        $request->attributes->set('_query_route', $request->queryRouteRaw());
        $request->attributes->set('_query_route_path', $request->getPathInfo());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Kernel::HANDLE_BEFORE => [['onKernelRequest', 40]],
        ];
    }
}
