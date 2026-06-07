<?php

namespace Pinoox\PinDoc\Api\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]

class ApiBody
{
    public function __construct(
        public string $description = '',
        public array $properties = [],
        public mixed $example = null,
    ) {
    }
}

