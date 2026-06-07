<?php

namespace Pinoox\PinDoc\Console;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;

trait ResolvesDocOutputPath
{
    protected function resolveDocOutputPath(string $package, string $format, object $generator, ?string $explicit, ?string $audience = null): string
    {
        if ($explicit !== null && $explicit !== '') {
            if ($this->isAbsoluteDocPath($explicit) || str_starts_with(str_replace('\\', '/', $explicit), '~')) {
                return $this->resolveProjectDocPath($explicit);
            }

            if (str_starts_with(str_replace('\\', '/', $explicit), 'apps/')) {
                return $this->resolveProjectDocPath($explicit);
            }

            return AppEngine::path($package, $explicit);
        }

        return AppEngine::path($package, $generator->defaultOutputRelativePath($package, $format, $audience));
    }

    protected function resolveProjectDocPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, '~')) {
            return SystemConfig::resolvePath($path);
        }

        return rtrim(str_replace('\\', '/', Loader::getBasePath()), '/') . '/' . ltrim($path, '/');
    }

    protected function isAbsoluteDocPath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/');
    }
}

