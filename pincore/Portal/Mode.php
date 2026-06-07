<?php

namespace Pinoox\Portal;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static string name(?string $package = NULL)
 * @method static bool debug(?string $package = NULL)
 * @method static bool is(string $mode, ?string $package = NULL)
 * @method static bool isProduction(?string $package = NULL)
 * @method static bool isDevelopment(?string $package = NULL)
 * @method static bool isTest(?string $package = NULL)
 * @method static bool isStaging(?string $package = NULL)
 * @method static string databaseConnection(?string $package = NULL)
 * @method static bool cacheEnabledByDefault(?string $package = NULL)
 * @method static string cacheMode(?string $package = NULL)
 * @method static string defaultLogLevel(?string $package = NULL)
 * @method static bool shouldValidateActions(?string $package = NULL)
 * @method static array profile(?string $package = NULL)
 * @method static RuntimeMode ___()
 *
 * @see RuntimeMode
 */
class Mode extends Portal
{
    public static function __register(): void
    {
        self::__bind(RuntimeMode::class)->setArguments([
            AppEngine::__ref(),
        ]);
    }

    public static function __name(): string
    {
        return 'mode';
    }

    /**
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }
}

