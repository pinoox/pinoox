<?php

namespace Pinoox\Terminal\Api;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'api:docs',
    description: 'Generate REST API documentation.',
)]
class ApiDocsCommand extends \Pinoox\Api\Console\ApiDocsCommand
{
}
