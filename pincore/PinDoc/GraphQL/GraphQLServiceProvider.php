<?php

namespace Pinoox\PinDoc\GraphQL;

use Pinoox\Portal\App\App;

class GraphQLServiceProvider
{
    /** @var array<string, true> */

    private static array $registered = [];

    public static function register(): void
    {
        $package = App::package();

        if ($package === null || $package === '' || isset(self::$registered[$package])) {
            return;
        }

        App::router()->add(
            '/graphql',
            [GraphQLExecutor::class, 'handle'],
            'graphql.execute',
            'POST',
            data: ['graphql' => true],
            tags: ['graphql'],
        );

        self::$registered[$package] = true;
    }
}

