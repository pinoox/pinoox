<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Validation\Factory as FactoryValidation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public function __construct(private readonly FactoryValidation $validation)
    {
    }

    public function onRequestSession(RequestEvent $event)
    {
        if ($event->getRequest()->hasSession()) {
            $session = $event->getRequest()->getSession();
            if (!$session->isStarted())
                $session->start();
        }
    }

    public function onRequestValidation(RequestEvent $event)
    {
        $event->getRequest()->setValidation($this->validation);
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequestSession'],
                ['onRequestValidation']
            ],
        ];
    }
}
