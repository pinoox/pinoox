<?php

namespace Pinoox\Component\Template\Theme;

/**
 * Declared theme contexts from app.php (site, panel, kids, ...).
 */
final class ThemeContextRegistry
{
    /**
     * @param array<string, mixed> $config
     */
    public static function hasContexts(array $config): bool
    {
        $contexts = $config['theme-contexts'] ?? null;

        if (!is_array($contexts) || $contexts === []) {
            return false;
        }

        foreach (array_keys($contexts) as $name) {
            if (is_string($name) && trim($name) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    public static function names(array $config): array
    {
        $contexts = $config['theme-contexts'] ?? null;

        if (!is_array($contexts) || $contexts === []) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($name) => is_string($name) ? trim($name) : '',
            array_keys($contexts),
        )));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function defaultName(array $config): string
    {
        $explicit = $config['theme-context'] ?? null;
        if (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit);
        }

        if (self::hasContexts($config)) {
            $first = array_key_first($config['theme-contexts']);

            return is_string($first) && $first !== '' ? $first : 'default';
        }

        return 'default';
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function context(array $config, string $name): array
    {
        if (!self::hasContexts($config)) {
            return [];
        }

        $ctx = $config['theme-contexts'][$name] ?? null;

        return is_array($ctx) ? $ctx : [];
    }

    /**
     * Build an effective app config snapshot for ThemeStack resolution.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function effectiveConfig(array $config, ?string $contextName = null): array
    {
        if (!self::hasContexts($config)) {
            return $config;
        }

        $contextName ??= self::defaultName($config);
        $ctx = self::context($config, $contextName);

        if ($ctx === []) {
            return $config;
        }

        $merged = $config;

        if (array_key_exists('theme', $ctx)) {
            $merged['theme'] = $ctx['theme'];
        }

        if (array_key_exists('extends', $ctx) || array_key_exists('theme-extends', $ctx)) {
            $merged['theme-extends'] = $ctx['extends'] ?? $ctx['theme-extends'];
        }

        if (array_key_exists('path-theme', $ctx)) {
            $merged['path-theme'] = $ctx['path-theme'];
        }

        if (isset($ctx['frontend']) && is_array($ctx['frontend'])) {
            $baseFrontend = is_array($config['frontend'] ?? null) ? $config['frontend'] : [];
            $merged['frontend'] = array_replace_recursive($baseFrontend, $ctx['frontend']);
        }

        return $merged;
    }
}

