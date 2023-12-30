<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Template\View;
use Pinoox\Portal\Path;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pinoox\Component\Http\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\NoConfigurationException;

class RouteListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();

        if ($e instanceof NotFoundHttpException && $e->getPrevious() instanceof NoConfigurationException) {
            $event->setResponse($this->createWelcomeResponse());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }

    private function createWelcomeResponse(): Response
    {
        $view = new View('no-route', Path::get('~pincore/resource/views/'));
        return new Response($view->render('home'));
    }
}
