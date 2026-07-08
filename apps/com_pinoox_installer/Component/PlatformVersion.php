<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Package\Pinx\PinxVersion;

final class PlatformVersion
{
    /**
     * Platform version name from {project}/platform/pinoox.config.php.
     */
    public static function label(): string
    {
        return trim(PinxVersion::platform()['name']);
    }
}
