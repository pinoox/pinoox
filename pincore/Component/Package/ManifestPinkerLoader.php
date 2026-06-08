<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Store\Baker\Pinker;
use Pinoox\Portal\Pinker as PinkerPortal;

/**
 * Resolve manifest PHP files (app.php, theme.php) via Pinker with default fallbacks.
 */
final class ManifestPinkerLoader
{
    /** @var array<string, mixed>|null */
    private static ?array $themeDefaults = null;

    /**
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public static function resolve(string $mainFile, array $defaults = []): array
    {
        if ($mainFile === '' || !is_file($mainFile)) {
            return $defaults;
        }

        $bakedFile = PinkerPortal::bakedFileFromSource($mainFile);
        $pinker = new Pinker($mainFile, $bakedFile);
        $pinker->dumping(true);

        $pickup = $pinker->pickup();

        return array_replace_recursive(
            $defaults,
            is_array($pickup) ? $pickup : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function themeDefaults(): array
    {
        if (self::$themeDefaults === null) {
            $defaults = include __DIR__ . '/data/theme-source.php';
            self::$themeDefaults = is_array($defaults) ? $defaults : [];
        }

        return self::$themeDefaults;
    }
}
