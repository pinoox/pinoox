<?php

namespace Pinoox\Component\Template\Theme;

use Pinoox\Component\Path\Url;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Url as UrlPortal;

final class ThemeAssets
{
    /**
     * Resolve a theme asset to a public URL, or return the filesystem path.
     *
     * @param list<string>|null $themePaths Active view theme paths (child-first); falls back to ThemeStack when null.
     */
    public static function resolve(
        string $link = '',
        ?string $theme = null,
        ?string $package = null,
        bool $asPath = false,
        ?array $themePaths = null,
    ): string {
        return self::resolveWithUrl(UrlPortal::___(), $link, $theme, $package, $asPath, $themePaths);
    }

    /**
     * @param list<string>|null $themePaths
     */
    public static function resolveWithUrl(
        Url $url,
        string $link = '',
        ?string $theme = null,
        ?string $package = null,
        bool $asPath = false,
        ?array $themePaths = null,
    ): string {
        $package = $url->activePackage($package);
        ['file' => $file, 'theme' => $theme] = self::parseThemedLink($link, $theme);

        if ($asPath) {
            return self::filesystem($file, $theme, $package, $themePaths);
        }

        $resolved = $theme !== null && $theme !== ''
            ? self::resolveFromTheme($file, $theme, $package)
            : self::resolveFromStack($file, $package, $themePaths);

        return $url->asset($resolved['segment'], $resolved['package']);
    }

    /**
     * @return array{segment: string, package: string}
     */
    public static function resolveSegment(
        string $file = '',
        ?string $theme = null,
        ?string $package = null,
        ?Url $url = null,
    ): array {
        $package = $url !== null
            ? $url->activePackage($package)
            : (($package !== null && $package !== '') ? $package : App::package());
        ['file' => $file, 'theme' => $theme] = self::parseThemedLink($file, $theme);

        return $theme !== null && $theme !== ''
            ? self::resolveFromTheme($file, $theme, $package)
            : self::resolveFromStack($file, $package, null);
    }

    /**
     * Parse optional @theme/ prefix in asset links.
     *
     * @return array{file: string, theme: ?string}
     */
    public static function parseThemedLink(string $link, ?string $theme = null): array
    {
        if ($theme !== null && $theme !== '') {
            return ['file' => $link, 'theme' => $theme];
        }

        if (!str_starts_with($link, '@')) {
            return ['file' => $link, 'theme' => null];
        }

        $rest = substr($link, 1);

        if ($rest === '') {
            return ['file' => '', 'theme' => null];
        }

        if (!str_contains($rest, '/')) {
            return ['file' => '', 'theme' => $rest];
        }

        [$themePart, $file] = explode('/', $rest, 2);

        return ['file' => $file, 'theme' => $themePart];
    }

    public static function publicSegment(ThemeReference $ref, string $file, string $pathTheme): string
    {
        $segment = $pathTheme . '/' . $ref->name;
        $file = ltrim(str_replace('\\', '/', $file), '/');

        if ($file !== '') {
            $segment .= '/' . $file;
        }

        return $segment;
    }

    /**
     * @param list<string>|null $themePaths
     */
    public static function filesystem(
        string $file,
        ?string $theme,
        ?string $package,
        ?array $themePaths = null,
    ): string {
        $package ??= App::package();

        if ($theme !== null && $theme !== '') {
            $ref = ThemeReference::parse($theme, $package);
            $dir = ThemeStack::directory($ref->name, $ref->package);

            if ($file === '') {
                return $dir;
            }

            return rtrim(str_replace('\\', '/', $dir), '/') . '/' . ltrim($file, '/');
        }

        $paths = $themePaths ?? ThemeStack::paths($package);
        $file = ltrim(str_replace('\\', '/', $file), '/');

        if ($file === '') {
            $base = $paths[0] ?? ThemeStack::directory(ThemeStack::activeName($package), $package);

            return rtrim(str_replace('\\', '/', $base), '/');
        }

        foreach ($paths as $themePath) {
            $candidate = rtrim(str_replace('\\', '/', $themePath), '/') . '/' . $file;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $base = $paths[0] ?? ThemeStack::directory(ThemeStack::activeName($package), $package);

        return rtrim(str_replace('\\', '/', $base), '/') . '/' . $file;
    }

    /**
     * @return array{segment: string, package: string}
     */
    private static function resolveFromTheme(string $file, string $theme, string $package): array
    {
        $ref = ThemeReference::parse($theme, $package);
        $pathTheme = ThemeStack::pathTheme($ref->package);

        return [
            'segment' => self::publicSegment($ref, $file, $pathTheme),
            'package' => $ref->package,
        ];
    }

    /**
     * @param list<string>|null $themePaths
     * @return array{segment: string, package: string}
     */
    private static function resolveFromStack(string $file, ?string $package, ?array $themePaths): array
    {
        $package ??= App::package();
        $stack = ThemeStack::resolve($package);
        $pathTheme = $stack['path_theme'];
        $file = ltrim(str_replace('\\', '/', $file), '/');

        $paths = $themePaths ?? $stack['paths'];
        $names = self::themeNamesForPaths($paths, $stack);

        if ($file === '') {
            $ref = new ThemeReference($stack['package'], $stack['name']);

            return [
                'segment' => self::publicSegment($ref, '', $pathTheme),
                'package' => $stack['package'],
            ];
        }

        foreach ($paths as $index => $themePath) {
            $candidate = rtrim(str_replace('\\', '/', $themePath), '/') . '/' . $file;
            if (!is_file($candidate)) {
                continue;
            }

            $themeName = $names[$index] ?? basename($themePath);
            $ref = new ThemeReference($stack['package'], $themeName);

            return [
                'segment' => self::publicSegment($ref, $file, $pathTheme),
                'package' => $stack['package'],
            ];
        }

        $ref = new ThemeReference($stack['package'], $stack['name']);

        return [
            'segment' => self::publicSegment($ref, $file, $pathTheme),
            'package' => $stack['package'],
        ];
    }

    /**
     * @param list<string> $paths
     * @param array{name: string, stack: list<string>, paths: list<string>, path_theme: string, package: string} $stack
     * @return list<string>
     */
    private static function themeNamesForPaths(array $paths, array $stack): array
    {
        if ($paths === $stack['paths']) {
            return $stack['stack'];
        }

        return array_map(
            static fn (string $themePath) => self::referenceFromThemePath($themePath, $stack['package'])->name,
            $paths,
        );
    }

    private static function referenceFromThemePath(string $themePath, string $defaultPackage): ThemeReference
    {
        $themePath = rtrim(str_replace('\\', '/', $themePath), '/');

        if (preg_match('#/apps/([^/]+)/([^/]+)/([^/]+)$#', $themePath, $matches) === 1) {
            return new ThemeReference($matches[1], $matches[3]);
        }

        return new ThemeReference($defaultPackage, basename($themePath));
    }
}
