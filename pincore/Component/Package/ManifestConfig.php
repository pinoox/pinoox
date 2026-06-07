<?php

namespace Pinoox\Component\Package;

/**
 * Dot-notation access for app.php / theme.php manifest arrays.
 */
final class ManifestConfig
{
    public static function get(array $data, ?string $key, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $data;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        $segments = explode('.', $key);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current)) {
                return $default;
            }

            if (array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            $kebab = self::toKebab($segment);
            if ($kebab !== $segment && array_key_exists($kebab, $current)) {
                $current = $current[$kebab];
                continue;
            }

            return $default;
        }

        return $current;
    }

    private static function toKebab(string $value): string
    {
        return strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $value));
    }
}
