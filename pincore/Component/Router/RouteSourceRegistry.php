<?php

namespace Pinoox\Component\Router;

use Closure;

/**
 * Stores where routes and named actions were registered (file, line, snippet).
 */
class RouteSourceRegistry
{
    /** @var array<string, array<string, mixed>> */

    private static array $routes = [];

    /** @var array<string, array<string, mixed>> */

    private static array $actions = [];

    /** @var list<string> Active route files while include() runs (survives Portal delegation). */

    private static array $loadingFileStack = [];

    public static function pushLoadingFile(string $file): void
    {
        $file = self::normalizePath($file);
        if ($file !== '') {
            self::$loadingFileStack[] = $file;
        }
    }

    public static function popLoadingFile(): void
    {
        array_pop(self::$loadingFileStack);
    }

    public static function currentLoadingFile(): ?string
    {
        $file = end(self::$loadingFileStack);

        return is_string($file) && $file !== '' ? $file : null;
    }

    public static function rememberRoute(string $name, mixed $declaredAction, array $trace, ?string $loadingFile = null): void
    {
        if ($name === '') {
            return;
        }

        self::$routes[$name] = self::buildEntry($declaredAction, $trace, $loadingFile);
    }

    public static function rememberAction(string $name, mixed $declaredAction, array $trace, ?string $loadingFile = null): void
    {
        if ($name === '') {
            return;
        }

        self::$actions[$name] = self::buildEntry($declaredAction, $trace, $loadingFile);
    }

    public static function route(string $name): ?array
    {
        return self::$routes[$name] ?? null;
    }

    public static function action(string $name): ?array
    {
        if (isset(self::$actions[$name])) {
            return self::$actions[$name];
        }

        foreach (self::$actions as $storedName => $entry) {
            if (str_ends_with($storedName, $name)) {
                return $entry;
            }
        }

        return null;
    }

    public static function reset(): void
    {
        self::$routes = [];
        self::$actions = [];
        self::$loadingFileStack = [];
    }

    private static function buildEntry(mixed $declaredAction, array $trace, ?string $loadingFile): array
    {
        if ($declaredAction instanceof Closure) {
            $closureMeta = self::closureLocation($declaredAction);
            if ($closureMeta !== null) {
                return $closureMeta;
            }
        }

        $loadingFile = self::normalizePath($loadingFile ?? '');
        if ($loadingFile === '') {
            $loadingFile = self::normalizePath(self::currentLoadingFile() ?? '');
        }

        $file = '';
        $line = 0;

        if ($loadingFile !== '' && is_file($loadingFile)) {
            $file = $loadingFile;
            $line = self::lineInTraceForFile($trace, $loadingFile) ?? 0;
        }

        if ($file === '' || self::isInfrastructureFrame(self::normalizePath($file))) {
            $frame = self::callerFrame($trace);
            $file = (string) ($frame['file'] ?? $file);
            $line = (int) ($frame['line'] ?? $line);
        } elseif ($line === 0) {
            $frame = self::lineFrameInTrace($trace, $loadingFile) ?? self::callerFrame($trace);
            $line = (int) ($frame['line'] ?? 0);
        }

        $file = self::normalizePath($file);

        return [
            'file' => $file,
            'line' => $line,
            'relative_file' => self::relativePath($file),
            'declared' => self::describeAction($declaredAction),
            'snippet' => self::readSnippet($file, $line),
            'is_closure' => $declaredAction instanceof Closure,
        ];
    }

    private static function normalizePath(?string $path): string
    {
        return str_replace('\\', '/', (string) $path);
    }

    private static function lineInTraceForFile(array $trace, string $file): ?int
    {
        $frame = self::lineFrameInTrace($trace, $file);
        if ($frame === null) {
            return null;
        }

        $line = (int) ($frame['line'] ?? 0);

        return $line > 0 ? $line : null;
    }

    private static function lineFrameInTrace(array $trace, string $file): ?array
    {
        $file = self::normalizePath($file);

        foreach ($trace as $frame) {
            if (self::normalizePath((string) ($frame['file'] ?? '')) !== $file) {
                continue;
            }

            return $frame;
        }

        return null;
    }

    private static function callerFrame(array $trace): ?array
    {
        $fallback = null;

        foreach ($trace as $frame) {
            $file = self::normalizePath((string) ($frame['file'] ?? ''));

            if (self::isInfrastructureFrame($file)) {
                continue;
            }

            if (self::isRouteSourceFile($file)) {
                return $frame;
            }

            $fallback ??= $frame;
        }

        return $fallback;
    }

    private static function isRouteSourceFile(string $file): bool
    {
        return str_contains($file, '/routes/')
            || str_contains($file, '/router/');
    }

    private static function isInfrastructureFrame(string $file): bool
    {
        if ($file === '' || str_contains($file, '/vendor/')) {
            return true;
        }

        foreach ([
            '/Component/Router/Router.php',
            '/Component/Router/RouteBuilder.php',
            '/Component/Router/RouteRegister.php',
            '/Component/Router/RouteEntryBuilder.php',
            '/Component/Router/Action/ActionBuilder.php',
            '/Component/Source/Portal.php',
            '/functions/router.php',
        ] as $pattern) {
            if (str_contains($file, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function describeAction(mixed $action): string
    {
        if ($action instanceof Closure) {
            return '{closure}';
        }

        if (is_array($action)) {
            return implode('::', array_map(static fn ($part) => is_string($part) ? $part : get_debug_type($part), $action));
        }

        return (string) $action;
    }

    public static function closureLocation(mixed $action): ?array
    {
        if (!$action instanceof Closure) {
            return null;
        }

        try {
            $reflection = new \ReflectionFunction($action);
        } catch (\ReflectionException) {
            return null;
        }

        $file = str_replace('\\', '/', $reflection->getFileName() ?: '');
        $line = (int) $reflection->getStartLine();

        if ($file === '' || $line < 1) {
            return null;
        }

        return [
            'file' => $file,
            'line' => $line,
            'relative_file' => self::relativePath($file),
            'declared' => '{closure}',
            'snippet' => self::readSnippet($file, $line),
            'is_closure' => true,
        ];
    }

    /**
     * @return list<array{number: int, highlight: bool, content: string}>
     */
    public static function readSnippet(string $file, int $line, int $radius = 2): array
    {
        if (!is_file($file) || $line < 1) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $start = max(0, $line - 1 - $radius);
        $end = min(count($lines), $line + $radius);
        $snippet = [];

        for ($index = $start; $index < $end; $index++) {
            $snippet[] = [
                'number' => $index + 1,
                'highlight' => ($index + 1) === $line,
                'content' => (string) $lines[$index],
            ];
        }

        return $snippet;
    }

    public static function relativePath(string $file): string
    {
        $file = str_replace('\\', '/', $file);

        if ($file === '') {
            return '';
        }

        $root = defined('PINOOX_BASE_PATH') ? str_replace('\\', '/', PINOOX_BASE_PATH) : '';

        if ($root !== '' && str_starts_with($file, $root)) {
            return ltrim(substr($file, strlen($root)), '/');
        }

        $appsPos = stripos($file, '/apps/');
        if ($appsPos !== false) {
            return ltrim(substr($file, $appsPos + 1), '/');
        }

        return basename($file);
    }
}

