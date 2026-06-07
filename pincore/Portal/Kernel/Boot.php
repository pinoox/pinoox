<?php

namespace Pinoox\Portal\Kernel;

use Pinoox\Component\Kernel\Boot\BootPipeline;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\AppProvider;

/**
 * @method static list<string> bootStages()
 * @method static BootPipeline ___pipeline()
 *
 * @see \Pinoox\Component\Package\AppProvider
 */
class Boot extends Portal
{
    public static function __register(): void
    {
    }

    public static function bootStages(): array
    {
        return AppProvider::___()->bootStages();
    }

    public static function __name(): string
    {
        return 'kernel.boot';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [];
    }
}

