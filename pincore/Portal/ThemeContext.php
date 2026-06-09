<?php

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\Template\Theme\ThemeContext as ThemeContextManager;

/**
 * @method static string|null active(?string $package = null)
 * @method static void activate(string $context, ?string $package = null)
 * @method static mixed using(string $context, callable $callback, ?string $package = null)
 * @method static void reset(?string $package = null)
 * @method static array info(?string $package = null)
 *
 * @see ThemeContextManager
 */
class ThemeContext extends Portal
{
    public static function __register(): void
    {
    }

    public static function __name(): string
    {
        return 'theme.context';
    }

    public static function active(?string $package = null): ?string
    {
        return ThemeContextManager::active($package);
    }

    public static function activate(string $context, ?string $package = null): void
    {
        ThemeContextManager::activate($context, $package);
    }

    public static function using(string $context, callable $callback, ?string $package = null): mixed
    {
        return ThemeContextManager::using($context, $callback, $package);
    }

    public static function reset(?string $package = null): void
    {
        ThemeContextManager::reset($package);
    }

    public static function info(?string $package = null): array
    {
        return ThemeContextManager::info($package);
    }
}

