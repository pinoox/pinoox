<?php

/**
 * Project-root paths for staged .pinx packages (outside any HMVC app folder).
 */
namespace App\com_pinoox_manager\Component;

use Pinoox\Component\File;

final class PackagePaths
{
    public const MANUAL = '~/downloads/packages/manual/';

    public const APPS = '~/downloads/apps/';

    public const TEMPLATES = '~/downloads/templates/';

    public static function manualDir(): string
    {
        return path(self::MANUAL);
    }

    public static function manualFile(string $filename): string
    {
        return path(self::MANUAL . basename($filename));
    }

    public static function ensureManualDir(): string
    {
        $dir = self::manualDir();
        File::make_folder($dir, true, 0755, true);

        return $dir;
    }
}
