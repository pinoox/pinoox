<?php

namespace Pinoox\PinDoc\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]

class GraphQLExample
{
    public function __construct(
        public string $query,
        public string $description = '',
    ) {
    }
}

