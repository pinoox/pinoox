<?php

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static string|null get(?string $package = NULL)
 * @method static bool is(string $mode, ?string $package = NULL)
 * @method static \Pinoox\Component\Mode ___()
 *
 * @see \Pinoox\Component\Mode
 */
class Mode extends Portal
{
    public static function __register(): void
    {
        $appMode = App::config()->get('mode');
        $coreMode = Config::name('~pinoox')->get('mode');

        self::__bind(\Pinoox\Component\Mode::class)->setArguments([
            AppEngine::__ref(),
            $coreMode,
            $appMode,
        ]);
    }


    public static function __app(): ?string
    {
        return App::package();
    }


    public static function __name(): string
    {
        return 'mode';
    }

    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }
}
