<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

use ReflectionClass;
use ReflectionMethod;

class MethodSourceReader
{
    public static function methodBody(ReflectionMethod $method): string
    {
        $source = self::readLines($method->getFileName(), $method->getStartLine(), $method->getEndLine());

        if ($source === '') {
            return '';
        }

        if (preg_match('/\{([\s\S]*)\}\s*$/', $source, $matches) !== 1) {
            return $source;
        }

        return trim($matches[1]);
    }

    public static function methodSource(ReflectionMethod $method): string
    {
        return self::readLines($method->getFileName(), $method->getStartLine(), $method->getEndLine());
    }

    public static function classSource(ReflectionClass $class): string
    {
        if ($class->getFileName() === false) {
            return '';
        }

        $contents = @file_get_contents($class->getFileName());

        return is_string($contents) ? $contents : '';
    }

    private static function readLines(false|string $file, int $start, int $end): string
    {
        if ($file === false || !is_readable($file) || $start <= 0 || $end < $start) {
            return '';
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if (!is_array($lines)) {
            return '';
        }

        $slice = array_slice($lines, $start - 1, $end - $start + 1);

        return implode("\n", $slice);
    }
}

