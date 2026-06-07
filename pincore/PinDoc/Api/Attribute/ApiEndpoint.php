<?php

namespace Pinoox\PinDoc\Api\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]

class ApiEndpoint
{
    public function __construct(
        public string $summary = '',
        public string $description = '',
        public string $tag = 'General',
        public bool $deprecated = false,
    ) {
    }
}

