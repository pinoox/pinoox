<?php

namespace Pinoox\Component\Kernel\Debug\Support;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\Source\PortalCallSite;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class PortalContextResolver
{
    public static function resolve(?FlattenException $exception = null): array
    {
        $site = PortalCallSite::last();
        $parsed = $exception !== null ? self::parseUndefinedMethod($exception->getMessage()) : null;

        if ($site === null && $exception !== null) {
            $site = self::inferFromTrace($exception);
        }

        if ($site === null && $parsed === null) {
            return [];
        }

        $context = array_filter([
            'portal' => $site['portal_short'] ?? null,
            'portal_class' => $site['portal'] ?? null,
            'method' => $site['method'] ?? ($parsed['method'] ?? null),
            'call' => $site['call'] ?? null,
            'target' => $site['target'] ?? ($parsed['class'] ?? null),
            'suggestion' => $parsed['suggestion'] ?? null,
            'file' => $site['relative_file'] ?? null,
            'line' => $site['line'] ?? null,
            'source' => !empty($site['snippet']) ? [
                'relative_file' => $site['relative_file'] ?? '',
                'line' => (int) ($site['line'] ?? 0),
                'snippet' => $site['snippet'],
            ] : null,
            'via_portal' => $site !== null || self::isPortalThrowable($exception),
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

        return $context;
    }

    private static function parseUndefinedMethod(string $message): ?array
    {
        if (!preg_match('/undefined method named "([^"]+)" of class "([^"]+)"(?:.*did you mean to call "([^"]+)")?/i', $message, $matches)) {
            return null;
        }

        return [
            'method' => $matches[1],
            'class' => $matches[2],
            'suggestion' => $matches[3] ?? null,
        ];
    }

    private static function isPortalThrowable(?FlattenException $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        $file = str_replace('\\', '/', $exception->getFile());

        return str_contains($file, '/Component/Source/Portal.php');
    }

    private static function inferFromTrace(FlattenException $exception): ?array
    {
        $parsed = self::parseUndefinedMethod($exception->getMessage());
        $trace = $exception->getTrace();
        $portalClass = '';
        $originFrame = null;

        foreach ($trace as $index => $frame) {
            $class = (string) ($frame['class'] ?? '');
            $function = (string) ($frame['function'] ?? '');

            if ($function !== '__callStatic' || !is_subclass_of($class, Portal::class, true)) {
                continue;
            }

            $portalClass = $class;
            $originFrame = self::nextUserFrame($trace, $index + 1);
            break;
        }

        if ($originFrame === null || $portalClass === '') {
            return null;
        }

        $method = (string) ($parsed['method'] ?? '');
        if ($method === '') {
            return null;
        }

        $file = str_replace('\\', '/', (string) ($originFrame['file'] ?? ''));
        $line = (int) ($originFrame['line'] ?? 0);
        $shortName = basename(str_replace('\\', '/', $portalClass));

        return [
            'portal' => $portalClass,
            'portal_short' => $shortName,
            'method' => $method,
            'args' => '',
            'call' => $shortName . '::' . $method . '(…)',
            'target' => $parsed['class'] ?? null,
            'file' => $file,
            'line' => $line,
            'relative_file' => \Pinoox\Component\Router\RouteSourceRegistry::relativePath($file),
            'snippet' => \Pinoox\Component\Router\RouteSourceRegistry::readSnippet($file, $line),
        ];
    }

    private static function nextUserFrame(array $trace, int $start): ?array
    {
        for ($i = $start, $count = count($trace); $i < $count; $i++) {
            $file = str_replace('\\', '/', (string) ($trace[$i]['file'] ?? ''));

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

            return $trace[$i];
        }

        return null;
    }
}

