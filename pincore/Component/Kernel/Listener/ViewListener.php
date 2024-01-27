<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Template\ViewInterface;
use Pinoox\Portal\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewListener implements EventSubscriberInterface
{
    public function onView(ViewEvent $event)
    {
        $response = $event->getControllerResult();

        if ($event->getRequestType() === -1) {
            if (empty($response) || is_bool($response))
                $response = '';
        }

        if (is_string($response)) {
            if (filter_var($response, FILTER_VALIDATE_URL)) {
                $event->setResponse(new RedirectResponse($response));
            } else {
                $event->setResponse(new Response($response));
            }
        } else if (is_bool($response)) {
            $event->setResponse(new Response($response ? 'true' : 'false'));
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
        return [KernelEvents::VIEW => ['onView'], Kernel::HANDLE_AFTER => ['onView']];
    }
}
