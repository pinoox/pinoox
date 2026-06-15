<?php

/**
 * Lucide icon resolver for manager UI.
 *
 * @see https://lucide.dev/
 *
 * app.php:
 *   '@lucide:settings'     explicit Lucide icon
 *   '@settings'            shorthand
 *   'icon.png'             custom file in app folder
 *   'icon_colors' => [...] optional gradient colors (enables tinted style)
 *   'icon_style' => 'gradient'|'crystal' optional style override
 */

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Url;

class AppIconPack
{
    private const MANAGER = 'com_pinoox_manager';

    private const MANAGER_FALLBACK = 'resources/default.png';

    private const DEFAULT_LUCIDE = 'box';

    /** @var array<string, string> */
    private const SYSTEM_LUCIDE = [
        'com_pinoox_welcome' => 'sparkles',
        'com_pinoox_manager' => 'layout-dashboard',
        'com_pinoox_installer' => 'download',
        'com_pinoox_comingsoon' => 'clock',
    ];

    public static function info(): array
    {
        return [
            'provider' => 'lucide',
            'provider_url' => 'https://lucide.dev/',
            'license' => 'ISC',
            'package' => 'lucide-vue-next',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function systemDefaults(): array
    {
        return self::SYSTEM_LUCIDE;
    }

    /**
     * @return array<string, string>
     */
    public static function usage(): array
    {
        return [
            'lucide' => "Set icon => '@lucide:settings' in app.php",
            'shorthand' => "Set icon => '@settings' (same as @lucide:settings)",
            'custom' => "Set icon => 'icon.png' and place the file in the app folder",
            'browse' => 'https://lucide.dev/icons/',
            'style_crystal' => 'Default Lucide tile is crystal/glass (no extra config)',
            'style_gradient' => "Set icon_style => 'gradient' or icon_colors => [...] (defaults to Pinoox logo colors)",
        ];
    }

    /**
     * @return array{url: string, source: string, pack_id: null, lucide: string, colors: list<string>, style: string, category: null}
     */
    public static function resolve(string $package, mixed $iconRef = null, mixed $appConfig = null): array
    {
        $iconRef = is_string($iconRef) ? trim($iconRef) : '';
        $appearance = self::resolveAppearance($appConfig);

        if ($iconRef !== '' && self::isCustomIconRef($iconRef) && self::hasCustomIconFile($package, $iconRef)) {
            $customUrl = Url::check(Url::asset($iconRef, $package), null);

            if ($customUrl !== null) {
                return self::result(
                    $customUrl,
                    'custom',
                    self::defaultLucideFor($package),
                    [],
                    'crystal',
                );
            }
        }

        $lucide = self::parseLucideRef($iconRef) ?? self::defaultLucideFor($package);

        return self::result('', 'lucide', $lucide, $appearance['colors'], $appearance['style']);
    }

    public static function defaultUrl(): string
    {
        return (string) Url::asset(self::MANAGER_FALLBACK, self::MANAGER);
    }

    private static function isCustomIconRef(string $iconRef): bool
    {
        return !str_starts_with($iconRef, '@');
    }

    private static function parseLucideRef(string $iconRef): ?string
    {
        if ($iconRef === '') {
            return null;
        }

        if ($iconRef === '@lucide') {
            return self::DEFAULT_LUCIDE;
        }

        if (str_starts_with($iconRef, '@lucide:')) {
            return self::normalizeLucideName(trim(substr($iconRef, 8)));
        }

        if (str_starts_with($iconRef, '@')) {
            $name = trim(substr($iconRef, 1));

            if ($name === '' || str_contains($name, '/') || str_contains($name, '.')) {
                return null;
            }

            return self::normalizeLucideName($name);
        }

        return null;
    }

    private static function normalizeLucideName(string $name): ?string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9-]/', '', $name) ?? '';

        return self::isValidLucideName($name) ? $name : null;
    }

    private static function isValidLucideName(string $name): bool
    {
        return $name !== '' && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name);
    }

    private static function defaultLucideFor(string $package): string
    {
        if (isset(self::SYSTEM_LUCIDE[$package])) {
            return self::SYSTEM_LUCIDE[$package];
        }

        if (str_starts_with($package, 'com_pinoox_')) {
            $slug = substr($package, strlen('com_pinoox_'));
            $candidate = self::normalizeLucideName($slug);

            if ($candidate !== null) {
                return $candidate;
            }
        }

        return self::DEFAULT_LUCIDE;
    }

    private static function hasCustomIconFile(string $package, string $iconRef): bool
    {
        if (!AppEngine::exists($package)) {
            return false;
        }

        $path = AppEngine::path($package) . '/' . ltrim($iconRef, '/');

        return is_file($path);
    }

    /**
     * @return list<string>
     */
    private static function defaultColors(): array
    {
        return ['#3399FF', '#0066FF', '#003B8E'];
    }

    /**
     * @return array{style: string, colors: list<string>}
     */
    private static function resolveAppearance(mixed $appConfig): array
    {
        if ($appConfig === null || !is_object($appConfig) || !method_exists($appConfig, 'get')) {
            return ['style' => 'crystal', 'colors' => []];
        }

        $rawColors = $appConfig->get('icon_colors') ?? $appConfig->get('icon-colors') ?? [];
        $iconStyle = strtolower(trim((string) ($appConfig->get('icon_style') ?? $appConfig->get('icon-style') ?? '')));

        $colors = [];

        if (is_array($rawColors)) {
            $colors = self::normalizeColors($rawColors);
        }

        if ($colors !== []) {
            return ['style' => 'gradient', 'colors' => $colors];
        }

        if (in_array($iconStyle, ['gradient', 'tinted', 'color'], true)) {
            return ['style' => 'gradient', 'colors' => self::defaultColors()];
        }

        if (in_array($iconStyle, ['crystal', 'glass'], true)) {
            return ['style' => 'crystal', 'colors' => []];
        }

        return ['style' => 'crystal', 'colors' => []];
    }

    /**
     * @return list<string>
     */
    private static function normalizeColors(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $colors = [];

        foreach ($raw as $color) {
            if (!is_string($color)) {
                continue;
            }

            $color = trim($color);

            if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1) {
                $colors[] = $color;
            }
        }

        return array_slice($colors, 0, 3);
    }

    /**
     * @param list<string> $colors
     * @return array{url: string, source: string, pack_id: null, lucide: string, colors: list<string>, style: string, category: null}
     */
    private static function result(string $url, string $source, string $lucide, array $colors, string $style): array
    {
        return [
            'url' => $url,
            'source' => $source,
            'pack_id' => null,
            'lucide' => $lucide,
            'colors' => $colors,
            'style' => $style,
            'category' => null,
        ];
    }
}
