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
    private static ?array $appDefaults = null;

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

        $pickup = self::pinkerFor($mainFile, $defaults)->pickup();

        return array_replace_recursive(
            $defaults,
            is_array($pickup) ? $pickup : [],
        );
    }

    /**
     * Pinker instance for manifest files; runtime defaults affect read merge and bake stripping.
     *
     * @param array<string, mixed> $runtimeDefaults
     */
    public static function pinkerFor(string $mainFile, array $runtimeDefaults = []): Pinker
    {
        $bakedFile = PinkerPortal::bakedFileFromSource($mainFile);
        $pinker = new Pinker($mainFile, $bakedFile);
        $pinker->dumping(true);

        if ($runtimeDefaults !== []) {
            $pinker->runtimeDefaults($runtimeDefaults);
        }

        return $pinker;
    }

    /**
     * @return array<string, mixed>
     */
    public static function appDefaults(): array
    {
        if (self::$appDefaults === null) {
            $defaults = include __DIR__ . '/data/source.php';
            self::$appDefaults = is_array($defaults) ? $defaults : [];
        }

        return self::$appDefaults;
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
