<?php

namespace Pinoox\Component\Template\Theme;

use Pinoox\Component\Package\AppManifest;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\Path;

final class ThemeStack
{
    /**
     * @return array{
     *     name: string,
     *     stack: list<string>,
     *     paths: list<string>,
     *     path_theme: string,
     *     package: string
     * }
     */
    public static function resolve(?string $package = null, ?string $context = null): array
    {
        $package = self::resolvePackage($package);
        $baseConfig = self::appConfig($package);

        if ($context === null && ThemeContextRegistry::hasContexts($baseConfig)) {
            $context = ThemeContext::active($package);
        }

        $config = ThemeContextRegistry::effectiveConfig($baseConfig, $context);

        $pathTheme = (string) ($config['path-theme'] ?? 'theme');
        $name = self::activeNameFromConfig($config);
        $configExtends = self::extendsFromConfig($config);

        $refs = self::buildReferenceStack(
            new ThemeReference($package, $name),
            $configExtends,
            $pathTheme,
        );

        $stack = array_map(static fn (ThemeReference $ref) => $ref->name, $refs);
        $paths = array_map(
            static fn (ThemeReference $ref) => self::themeDirectory($ref->package, $ref->name, $pathTheme),
            $refs,
        );

        return [
            'name' => $name,
            'stack' => $stack,
            'paths' => $paths,
            'path_theme' => $pathTheme,
            'package' => $package,
            'context' => $context,
        ];
    }

    public static function activeName(?string $package = null): string
    {
        return self::resolve($package)['name'];
    }

    /**
     * @return list<string>
     */
    public static function paths(?string $package = null): array
    {
        return self::resolve($package)['paths'];
    }

    /**
     * @return list<string>
     */
    public static function stack(?string $package = null): array
    {
        return self::resolve($package)['stack'];
    }

    public static function pathTheme(?string $package = null): string
    {
        $package = self::resolvePackage($package);

        return (string) (self::appConfig($package)['path-theme'] ?? 'theme');
    }

    public static function directory(string $themeName, ?string $package = null, ?string $pathTheme = null): string
    {
        $package = self::resolvePackage($package);

        return self::themeDirectory($package, $themeName, $pathTheme);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function activeNameFromConfig(array $config): string
    {
        $theme = $config['theme'] ?? 'default';

        if (is_array($theme)) {
            $name = $theme['name'] ?? $theme['theme'] ?? null;

            return is_string($name) && $name !== '' ? $name : 'default';
        }

        return is_string($theme) && $theme !== '' ? $theme : 'default';
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    public static function extendsFromConfig(array $config): array
    {
        $extends = [];

        if (isset($config['theme-extends'])) {
            $extends = array_merge($extends, self::normalizeExtendsList($config['theme-extends']));
        }

        if (isset($config['theme_extends'])) {
            $extends = array_merge($extends, self::normalizeExtendsList($config['theme_extends']));
        }

        $theme = $config['theme'] ?? null;
        if (is_array($theme) && !empty($theme['extends'])) {
            $extends = array_merge($extends, self::normalizeExtendsList($theme['extends']));
        }

        return self::uniqueExtends($extends);
    }

    /**
     * @param list<string> $configExtends
     * @return list<ThemeReference>
     */
    private static function buildReferenceStack(ThemeReference $active, array $configExtends, string $pathTheme): array
    {
        /** @var array<string, true> $visited */
        $visited = [];
        $chain = [$active];

        $manifestExtends = self::manifestExtends($active->package, $active->name, $pathTheme);
        $extends = $manifestExtends !== [] ? $manifestExtends : $configExtends;

        foreach ($extends as $extendRef) {
            $parent = ThemeReference::parse($extendRef, $active->package);
            foreach (self::collectAncestorReferences($parent, $visited, $pathTheme) as $ancestor) {
                if (!self::containsReference($chain, $ancestor)) {
                    $chain[] = $ancestor;
                }
            }
        }

        return $chain;
    }

    /**
     * @param array<string, true> $visited
     * @return list<ThemeReference>
     */
    private static function collectAncestorReferences(ThemeReference $ref, array &$visited, string $pathTheme): array
    {
        $key = $ref->key();

        if (isset($visited[$key])) {
            throw new \RuntimeException(sprintf('Circular theme inheritance detected at %s.', $key));
        }

        $visited[$key] = true;

        $chain = [$ref];

        foreach (self::manifestExtends($ref->package, $ref->name, $pathTheme) as $extendRef) {
            $parent = ThemeReference::parse($extendRef, $ref->package);

            foreach (self::collectAncestorReferences($parent, $visited, $pathTheme) as $ancestor) {
                if (!self::containsReference($chain, $ancestor)) {
                    $chain[] = $ancestor;
                }
            }
        }

        return $chain;
    }

    /**
     * @param list<ThemeReference> $chain
     */
    private static function containsReference(array $chain, ThemeReference $ref): bool
    {
        foreach ($chain as $item) {
            if ($item->key() === $ref->key()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function manifestExtends(string $package, string $themeName, string $pathTheme): array
    {
        $manifest = ThemeManifest::load($package, $themeName, $pathTheme);

        return $manifest?->extends() ?? [];
    }

    private static function themeDirectory(string $package, string $themeName, ?string $pathTheme = null): string
    {
        $pathTheme ??= (string) (self::appConfig($package)['path-theme'] ?? 'theme');

        if ($package !== '' && AppEnginePortal::exists($package)) {
            return rtrim(str_replace('\\', '/', AppEnginePortal::path($package, $pathTheme . '/' . $themeName)), '/');
        }

        if ($package !== '') {
            return rtrim(str_replace('\\', '/', Path::get($pathTheme . '/' . $themeName, $package)), '/');
        }

        return rtrim(str_replace('\\', '/', Path::get($pathTheme . '/' . $themeName)), '/');
    }

    /**
     * @return array<string, mixed>
     */
    public static function appConfig(?string $package = null): array
    {
        return AppManifest::load($package);
    }

    private static function resolvePackage(?string $package): string
    {
        if (is_string($package) && $package !== '') {
            return $package;
        }

        try {
            $active = App::package();

            return is_string($active) && $active !== '' ? $active : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @return list<string>
     */
    private static function normalizeExtendsList(mixed $extends): array
    {
        if (is_string($extends)) {
            $extends = trim($extends);

            return $extends === '' ? [] : [$extends];
        }

        if (!is_array($extends)) {
            return [];
        }

        $list = [];
        foreach ($extends as $item) {
            if (is_string($item) && trim($item) !== '') {
                $list[] = trim($item);
            }
        }

        return $list;
    }

    /**
     * @param list<string> $extends
     * @return list<string>
     */
    private static function uniqueExtends(array $extends): array
    {
        $unique = [];

        foreach ($extends as $extend) {
            if (!in_array($extend, $unique, true)) {
                $unique[] = $extend;
            }
        }

        return $unique;
    }
}

