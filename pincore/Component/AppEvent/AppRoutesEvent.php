<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Component\Event\Event;
use Pinoox\Component\Router\Router;
use Pinoox\Support\Event\Dispatchable;

class AppRoutesEvent extends Event
{
    use Dispatchable;

    public static $eventName = AppEventNames::ROUTES;

    public function __construct(
        public readonly string $package,
        public readonly Router $router,
        public readonly AppRegister $register,
    ) {
    }
}

