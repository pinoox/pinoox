<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Router\QueryRouteResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class QueryRouteCanonicalListener implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request instanceof Request) {
            return;
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $parameter = QueryRouteResolver::parameter();

        if (!$request->query->has($parameter)) {
            return;
        }

        $canonicalPath = QueryRouteResolver::canonicalPathForRequest($request);

        if ($canonicalPath === null) {
            return;
        }

        $canonicalUrl = QueryRouteResolver::canonicalUrlForRequest($request);
        $current = $request->getSchemeAndHttpHost() . $request->getRequestUri();

        if ($canonicalUrl !== null && QueryRouteResolver::urlsEquivalent($current, $canonicalUrl)) {
            return;
        }

        $event->setResponse(new RedirectResponse($canonicalPath, 301));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Kernel::HANDLE_BEFORE => [['onKernelRequest', 48]],
        ];
    }
}

