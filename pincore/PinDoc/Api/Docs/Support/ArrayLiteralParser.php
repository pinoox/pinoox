<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

class ArrayLiteralParser
{
    public static function extractReturnArrays(string $source): array
    {
        $results = [];
        $offset = 0;

        while (($pos = stripos($source, 'return', $offset)) !== false) {
            $after = ltrim(substr($source, $pos + 6));

            if (str_starts_with($after, '$this->')) {
                $offset = $pos + 6;
                continue;
            }

            if (!str_starts_with($after, '[')) {
                $offset = $pos + 6;
                continue;
            }

            $literal = self::readBracketLiteral($after, '[', ']');

            if ($literal !== null) {
                $parsed = self::parseLiteral($literal);

                if ($parsed !== null) {
                    $results[] = $parsed;
                }
            }

            $offset = $pos + 6;
        }

        return $results;
    }

    public static function parseLiteral(string $literal): ?array
    {
        $literal = trim($literal);

        if ($literal === '' || !str_starts_with($literal, '[')) {
            return null;
        }

        $php = '$__pinoox_doc = ' . $literal . ';';

        try {
            eval($php);
        } catch (\Throwable) {
            return self::parseLoose($literal);
        }

        return isset($__pinoox_doc) && is_array($__pinoox_doc) ? self::normalizeValues($__pinoox_doc) : null;
    }

    public static function extractValidationRules(string $source): array
    {
        if (preg_match('/\$request->validation\s*\(\s*\[(.*?)\]\s*\)/s', $source, $matches) !== 1) {
            return [];
        }

        $literal = self::readBracketLiteral('[' . $matches[1] . ']', '[', ']');

        if ($literal === null) {
            return [];
        }

        $parsed = self::parseLiteral($literal);

        return is_array($parsed) ? $parsed : [];
    }

    public static function extractRequestKeyList(string $source): array
    {
        if (preg_match('/\$request->(?:request|json)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches) === 1) {
            return array_values(array_filter(array_map('trim', explode(',', $matches[1]))));
        }

        if (preg_match('/\$request->(?:request|json)->all\s*\(\s*[\'"](\w+)[\'"]\s*\)/', $source, $matches) === 1) {
            return [$matches[1]];
        }

        return [];
    }

    public static function extractCommaSeparatedKeys(string $source, string $needle): array
    {
        if (preg_match('/' . preg_quote($needle, '/') . '\s*,\s*[\'"]([^\'"]+)[\'"]/', $source, $matches) !== 1) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $matches[1]))));
    }

    private static function readBracketLiteral(string $source, string $open, string $close): ?string
    {
        $start = strpos($source, $open);

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($source);
        $inString = false;
        $stringChar = '';

        for ($i = $start; $i < $length; $i++) {
            $char = $source[$i];

            if ($inString) {
                if ($char === $stringChar && ($i === 0 || $source[$i - 1] !== '\\')) {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private static function parseLoose(string $literal): ?array
    {
        $schema = [];

        if (preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*([^,\]]+)/', $literal, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $schema[$match[1]] = self::normalizeScalar(trim($match[2]));
            }
        }

        return $schema === [] ? null : $schema;
    }

    private static function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = self::normalizeValues($value);
                continue;
            }

            $normalized[$key] = self::normalizeScalar(is_string($value) ? $value : $value);
        }

        return $normalized;
    }

    private static function normalizeScalar(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^(time|now)\(\)$/i', $value) === 1) {
            return 1710000000;
        }

        return $value;
    }
}

