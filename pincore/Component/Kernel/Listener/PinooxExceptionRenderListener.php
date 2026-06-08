<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Debug\PinooxHtmlErrorRenderer;
use Pinoox\Component\Kernel\Debug\Support\ExceptionContext;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Transport\TransportContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders the Pinoox Exception page for guest-app failures inside {@see \Pinoox\Component\Package\App::meeting()}.
 *
 * Kernel sub-requests catch exceptions before PHP's global handler; this listener bridges that gap for app-view / meetingHandle flows.
 */
class PinooxExceptionRenderListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!RuntimeMode::bootDebugEnabled() || !TransportContext::inMeeting()) {
            return;
        }

        if ($event->hasResponse()) {
            return;
        }

        $projectDir = ExceptionContext::collect()['project_root'];
        $renderer = new PinooxHtmlErrorRenderer(true, null, null, $projectDir);
        $flattened = $renderer->render($event->getThrowable());

        $event->setResponse(new Response(
            $flattened->getAsString(),
            $flattened->getStatusCode(),
            $flattened->getHeaders(),
        ));
        $event->allowCustomResponseCode();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -128],
        ];
    }
}
