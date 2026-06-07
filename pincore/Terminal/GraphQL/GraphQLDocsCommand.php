<?php

namespace Pinoox\Terminal\GraphQL;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'graphql:docs',
    description: 'Generate GraphQL schema docs (Markdown or HTML) for an app',
)]

class GraphQLDocsCommand extends \Pinoox\PinDoc\GraphQL\Console\GraphQLDocsCommand
{
}

