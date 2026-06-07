<?php

namespace Pinoox\Component\Cache;

/**
 * Read/write Pinker-style PHP cache files (return array).
 */
final class PhpCacheFile
{

    public const EXT = 'php';

    public const LEGACY_EXT = 'json';

    /**
     * @param array<string, mixed> $data
     */
    public static function write(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::unlinkLegacy($path);

        file_put_contents($path, "<?php\n\nreturn " . self::export($data) . ";\n");
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function read(string $path): ?array
    {
        if (is_file($path)) {
            $data = include $path;

            return is_array($data) ? $data : null;
        }

        $legacy = self::legacyPath($path);
        if (!is_file($legacy)) {
            return null;
        }

        $json = file_get_contents($legacy);
        if (!is_string($json) || $json === '') {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        if (str_ends_with($path, '.' . self::EXT)) {
            self::write($path, $data);
        }

        return $data;
    }

    public static function exists(string $path): bool
    {
        return is_file($path) || is_file(self::legacyPath($path));
    }

    public static function unlink(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }

        self::unlinkLegacy($path);
    }

    public static function legacyPath(string $path): string
    {
        if (str_ends_with($path, '.' . self::EXT)) {
            return substr($path, 0, -strlen(self::EXT)) . self::LEGACY_EXT;
        }

        if (str_ends_with($path, '.' . self::LEGACY_EXT)) {
            return $path;
        }

        return $path . '.' . self::LEGACY_EXT;
    }

    private static function unlinkLegacy(string $path): void
    {
        $legacy = self::legacyPath($path);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }

    private static function export(mixed $data, int $level = 0): string
    {
        if (!is_array($data)) {
            return var_export($data, true);
        }

        if ($data === []) {
            return '[]';
        }

        $isList = array_is_list($data);
        if ($isList && $level > 0) {
            $items = [];
            foreach ($data as $value) {
                $items[] = self::export($value, $level + 1);
            }

            return '[' . implode(', ', $items) . ']';
        }

        $indent = str_repeat('    ', $level);
        $childIndent = str_repeat('    ', $level + 1);
        $items = [];

        foreach ($data as $key => $value) {
            $exportedKey = is_int($key) ? $key : var_export($key, true);
            $items[] = $childIndent . $exportedKey . ' => ' . self::export($value, $level + 1);
        }

        return "[\n" . implode(",\n", $items) . "\n" . $indent . ']';
    }
}

