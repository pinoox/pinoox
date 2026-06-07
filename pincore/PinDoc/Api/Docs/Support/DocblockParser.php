<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

class DocblockParser
{
    public static function parse(?string $docblock): array
    {
        if ($docblock === null || trim($docblock) === '') {
            return [
                'summary' => '',
                'description' => '',
                'params' => [],
                'return' => '',
            ];
        }

        $docblock = preg_replace('/^\/\*\*|\*\/$/', '', $docblock) ?? $docblock;
        $lines = preg_split('/\r\n|\r|\n/', $docblock) ?: [];
        $summary = '';
        $descriptionLines = [];
        $params = [];
        $return = '';
        $inDescription = false;

        foreach ($lines as $line) {
            $line = trim(ltrim(trim($line), '*'));

            if ($line === '' || str_starts_with($line, '@')) {
                if ($line !== '' && str_starts_with($line, '@param')) {
                    if (preg_match('/@param\s+(\S+)\s+\$(\w+)\s*(.*)$/u', $line, $matches) === 1) {
                        $params[$matches[2]] = [
                            'type' => self::normalizeType($matches[1]),
                            'description' => trim($matches[3]),
                        ];
                    }
                } elseif ($line !== '' && str_starts_with($line, '@return')) {
                    $return = trim(preg_replace('/^@return\s+/', '', $line) ?? '');
                }

                if ($line !== '') {
                    $inDescription = true;
                }

                continue;
            }

            if ($summary === '') {
                $summary = $line;
                continue;
            }

            $descriptionLines[] = $line;
        }

        return [
            'summary' => $summary,
            'description' => trim(implode("\n", $descriptionLines)),
            'params' => $params,
            'return' => $return,
        ];
    }

    private static function normalizeType(string $type): string
    {
        $type = trim($type, '[]');

        return match (strtolower($type)) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }
}

