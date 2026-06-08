<?php

namespace Pinoox\Component\User\Event;

use Pinoox\Component\Event\Event;
use Pinoox\Support\Event\Dispatchable;
use Pinoox\Model\UserModel;

class UserLoggedOut extends Event
{
    use Dispatchable;

    public static $eventName = 'user.logged_out';

    public function __construct(public readonly ?UserModel $user)
    {
    }
}

