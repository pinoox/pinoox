<?php

namespace App\com_pinoox_installer\Component;

use RuntimeException;

final class SetupException extends RuntimeException
{
    public function __construct(
        private readonly string $messageKey,
    ) {
        parent::__construct($messageKey);
    }

    public function messageKey(): string
    {
        return $this->messageKey;
    }
}
