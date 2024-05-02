<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Helpers\HelperResponse;
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

        $normalize = HelperResponse::normalize($response);
        if($normalize !== $response)
            $event->setResponse($normalize);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onView'], Kernel::HANDLE_AFTER => ['onView']];
    }
}
