<?php

namespace Pinoox\Component\Source;

use Pinoox\Component\Router\RouteSourceRegistry;

/**
 * Remembers the userland call site of the most recent Portal static dispatch.
 */
class PortalCallSite
{
    private static ?array $last = null;

    public static function capture(string $portalClass, string $method, array $args, array $trace): void
    {
        $frame = self::callerFrame($trace);
        if ($frame === null) {
            self::$last = null;

            return;
        }

        $file = str_replace('\\', '/', (string) ($frame['file'] ?? ''));
        $line = (int) ($frame['line'] ?? 0);
        $shortName = self::shortClassName($portalClass);

        self::$last = [
            'portal' => $portalClass,
            'portal_short' => $shortName,
            'method' => $method,
            'args' => self::formatArgs($args),
            'call' => $shortName . '::' . $method . '(' . self::formatArgs($args) . ')',
            'target' => self::resolveTargetClass($portalClass),
            'file' => $file,
            'line' => $line,
            'relative_file' => RouteSourceRegistry::relativePath($file),
            'snippet' => RouteSourceRegistry::readSnippet($file, $line),
        ];
    }

    public static function last(): ?array
    {
        return self::$last;
    }

    public static function reset(): void
    {
        self::$last = null;
    }

    private static function callerFrame(array $trace): ?array
    {
        foreach ($trace as $frame) {
            $file = str_replace('\\', '/', (string) ($frame['file'] ?? ''));

            if ($file === '' || str_contains($file, '/vendor/')) {
                continue;
            }

            if (str_contains($file, '/Component/Source/Portal.php')) {
                continue;
            }

            if (preg_match('#/Portal/[^/]+\.php$#', $file)) {
                continue;
            }

            if (str_contains($file, '/Component/Kernel/')) {
                continue;
            }

            return $frame;
        }

        return null;
    }

    private static function shortClassName(string $class): string
    {
        $parts = explode('\\', $class);

        return (string) end($parts);
    }

    private static function resolveTargetClass(string $portalClass): ?string
    {
        if (!is_subclass_of($portalClass, Portal::class)) {
            return null;
        }

        try {
            if (method_exists($portalClass, '__class')) {
                $target = $portalClass::__class();

                return is_string($target) && $target !== '' ? $target : null;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private static function formatArgs(array $args, int $limit = 4): string
    {
        if ($args === []) {
            return '';
        }

        $parts = [];
        foreach (array_slice($args, 0, $limit) as $arg) {
            $parts[] = self::formatArg($arg);
        }

        if (count($args) > $limit) {
            $parts[] = '…';
        }

        return implode(', ', $parts);
    }

    private static function formatArg(mixed $arg): string
    {
        if (is_string($arg)) {
            $value = strlen($arg) > 48 ? substr($arg, 0, 48) . '…' : $arg;

            return "'" . str_replace("'", "\\'", $value) . "'";
        }

        if (is_int($arg) || is_float($arg)) {
            return (string) $arg;
        }

        if (is_bool($arg)) {
            return $arg ? 'true' : 'false';
        }

        if ($arg === null) {
            return 'null';
        }

        if (is_array($arg)) {
            return 'array(' . count($arg) . ')';
        }

        if (is_object($arg)) {
            return 'new ' . self::shortClassName($arg::class) . '()';
        }

        return get_debug_type($arg);
    }
}

