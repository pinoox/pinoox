<?php

namespace Pinoox\PinDoc\GraphQL\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]

class GraphQLOperation
{
    public function __construct(
        public string $summary = '',
        public string $description = '',
        public string $tag = 'General',
        public string $type = 'query',
    ) {
    }
}

