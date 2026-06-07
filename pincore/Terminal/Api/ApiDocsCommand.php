<?php

namespace Pinoox\Terminal\Api;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'api:docs',
    description: 'Generate REST API docs (Markdown or HTML) for an app',
)]

class ApiDocsCommand extends \Pinoox\PinDoc\Api\Console\ApiDocsCommand
{
}

