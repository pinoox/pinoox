<?php

namespace Pinoox\Component\User;

use Pinoox\Model\UserModel;

readonly class LoginResult
{
    public function __construct(
        public bool $success,
        public ?string $reason = null,
        public ?string $message = null,
        public ?string $token = null,
        public ?UserModel $user = null,
    ) {
    }

    public static function ok(string $token, UserModel $user): self
    {
        return new self(true, token: $token, user: $user);
    }

    public static function fail(string $reason, ?string $message = null): self
    {
        return new self(false, $reason, $message);
    }
}

