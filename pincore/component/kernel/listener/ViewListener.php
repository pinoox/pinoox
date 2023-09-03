<?php

namespace pinoox\component\kernel\listener;

use pinoox\component\template\ViewInterface;
use pinoox\portal\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use pinoox\component\http\JsonResponse;
use pinoox\component\http\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewListener implements EventSubscriberInterface
{
    public function onView(ViewEvent $event)
    {
        $response = $event->getControllerResult();

        if (is_string($response)) {
            $event->setResponse(new Response($response));
        } else if (is_numeric($response)) {
            $event->setResponse(new Response(strval($response)));
        } else if (is_array($response)) {
            $event->setResponse(new JsonResponse($response));
        } else if ($response instanceof ViewInterface) {
            $event->setResponse(new Response($response->getContentReady()));
        } else if ($response instanceof View) {
            $event->setResponse(new Response($response->getContentReady()));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onView']];
    }
}
