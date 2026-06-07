<?php

namespace Pinoox\Component\User\Event;

use Pinoox\Component\Event\Event;
use Pinoox\Support\Event\Dispatchable;

class UserLoginFailed extends Event
{
    use Dispatchable;

    public static $eventName = 'user.login_failed';

    /**
     * @param array<string, mixed> $credentials
     */
    public function __construct(
        public readonly array $credentials,
        public readonly string $reason,
    ) {
    }
}

