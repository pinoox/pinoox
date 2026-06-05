<?php

namespace Pinoox\Terminal\GraphQL;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'graphql:docs',
    description: 'Generate GraphQL documentation.',
)]
class GraphQLDocsCommand extends \Pinoox\GraphQL\Console\GraphQLDocsCommand
{
}
