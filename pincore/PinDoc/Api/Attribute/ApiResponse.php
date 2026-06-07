<?php

namespace Pinoox\PinDoc\Api\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]

class ApiResponse
{
    public function __construct(
        public int $status = 200,
        public string $description = 'Success',
        public mixed $example = null,
    ) {
    }
}

