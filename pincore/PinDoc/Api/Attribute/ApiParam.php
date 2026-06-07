<?php

namespace Pinoox\PinDoc\Api\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]

class ApiParam
{
    public function __construct(
        public string $name,
        public string $in = 'path',
        public string $type = 'string',
        public bool $required = false,
        public string $description = '',
        public mixed $example = null,
    ) {
    }
}

