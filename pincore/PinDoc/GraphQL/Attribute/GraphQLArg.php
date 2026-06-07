<?php

namespace Pinoox\PinDoc\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]

class GraphQLArg
{
    public function __construct(
        public string $name,
        public string $type = 'String',
        public bool $required = false,
        public string $description = '',
        public mixed $example = null,
    ) {
    }
}

