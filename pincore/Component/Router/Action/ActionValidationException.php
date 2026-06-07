<?php

namespace Pinoox\Component\Router\Action;

use RuntimeException;

class ActionValidationException extends RuntimeException
{
    /** @param list<string> $errors */

    public function __construct(
        private readonly array $errors,
        string $message = 'Router action validation failed.',
    ) {
        parent::__construct($message . "\n" . implode("\n", $errors));
    }

    /** @return list<string> */

    public function errors(): array
    {
        return $this->errors;
    }
}

