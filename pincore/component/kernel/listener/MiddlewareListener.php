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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class MiddlewareListener implements EventSubscriberInterface
{
    private array $middlewares = [];

    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $next = function () use ($event) {
            $this->executeNextMiddleware($event);
        };

        foreach (array_reverse($this->middlewares) as $middleware) {
            $middleware($event->getRequest(), $next);
        }
    }

    private function executeNextMiddleware(RequestEvent $event): void
    {
        $event->stopPropagation();
        $this->onKernelRequest($event);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest',-21333],
        ];
    }
}
