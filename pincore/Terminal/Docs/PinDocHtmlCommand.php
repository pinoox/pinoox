<?php

namespace Pinoox\Terminal\Docs;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'pindoc:html',
    description: 'Build PinDoc HTML from API docs and/or custom Markdown.',
)]

class PinDocHtmlCommand extends \Pinoox\PinDoc\Console\PinDocHtmlCommand
{
}

