<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Component\Event\Event;
use Pinoox\Support\Event\Dispatchable;

class AppBootEvent extends Event
{
    use Dispatchable;

    public static $eventName = AppEventNames::BOOTING;

    public function __construct(
        public readonly string $package,
        public readonly AppRegister $register,
        public readonly bool $asExtender = false,
    ) {
    }
}

