<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Component\Event\Event;
use Pinoox\Support\Event\Dispatchable;

class AppApiEvent extends Event
{
    use Dispatchable;

    public static $eventName = AppEventNames::API;

    public function __construct(
        public readonly string $package,
        public readonly AppRegister $register,
    ) {
    }
}

