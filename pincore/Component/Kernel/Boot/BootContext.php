<?php

namespace Pinoox\Component\Kernel\Boot;

use Composer\Autoload\ClassLoader;
use Pinoox\Component\Package\App;

final class BootContext
{
    public function __construct(
        public readonly App $app,
        public readonly ClassLoader $classLoader,
    ) {
    }

    public function package(): string
    {
        return (string) $this->app->package();
    }

    public function path(string $relative = ''): string
    {
        return $this->app->path($relative);
    }
}

