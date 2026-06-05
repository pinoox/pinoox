<?php

namespace Pinoox\GraphQL;

use Pinoox\Portal\Router;

class GraphQLServiceProvider
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        Router::post('/graphql', [GraphQLExecutor::class, 'handle'], 'graphql.execute', data: [
            'graphql' => true,
        ], tags: ['graphql']);

        self::$registered = true;
    }
}
