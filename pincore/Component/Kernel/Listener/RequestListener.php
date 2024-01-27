<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Validation\Factory as FactoryValidation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public function __construct(private readonly FactoryValidation $validation)
    {
    }

    public function onRequest(RequestEvent $event)
    {
        $event->getRequest()->setValidation($this->validation);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest'],
        ];
    }
}
