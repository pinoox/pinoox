<?php

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\Transport\TransportConfig;

/**
 * @method static string package(string $key)
 * @method static string|null authSource(?string $hostPackage = null)
 * @method static bool sharesAuthWith(string $guestPackage, string $hostPackage)
 * @method static string|null platformAuthPackage()
 *
 * @see TransportConfig
 */
class Transport extends Portal
{
    public static function __register(): void
    {
        self::__bind(TransportConfig::class);
    }

    public static function __name(): string
    {
        return 'transport';
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
