<?php

namespace Pinoox\Component\Router\Action;

class ActionReference
{

    public const GLOBAL_PREFIX = '@';

    public const SCOPED_PREFIX = '&';

    public static function isReference(mixed $value): bool
    {
        return is_string($value) && (str_starts_with($value, self::GLOBAL_PREFIX) || str_starts_with($value, self::SCOPED_PREFIX));
    }

    /**
     * @return array{raw: string, prefix: string, short: string, scoped: bool}|null
     */
    public static function parse(string $reference): ?array
    {
        if (!self::isReference($reference)) {
            return null;
        }

        $scoped = str_starts_with($reference, self::SCOPED_PREFIX);
        $prefix = $scoped ? self::SCOPED_PREFIX : self::GLOBAL_PREFIX;

        return [
            'raw' => $reference,
            'prefix' => $prefix,
            'short' => ltrim($reference, '@&'),
            'scoped' => $scoped,
        ];
    }

    /**
     * @param list<string> $registeredKeys
     * @return list<string>
     */
    public static function candidateKeys(string $reference, string $collectionPrefix, array $registeredKeys): array
    {
        $parsed = self::parse($reference);
        if ($parsed === null) {
            return [];
        }

        $short = $parsed['short'];
        $candidates = [];

        if ($parsed['scoped']) {
            $candidates[] = $collectionPrefix . $short;
        }

        $candidates[] = $short;

        if ($collectionPrefix !== '') {
            $candidates[] = $collectionPrefix . $short;
        }

        foreach ($registeredKeys as $key) {
            if (str_ends_with($key, $short) || str_ends_with($key, '.' . $short)) {
                $candidates[] = $key;
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * @param list<string> $registeredKeys
     */
    public static function resolveKey(string $reference, string $collectionPrefix, array $registeredKeys): ?string
    {
        foreach (self::candidateKeys($reference, $collectionPrefix, $registeredKeys) as $candidate) {
            if (in_array($candidate, $registeredKeys, true)) {
                return $candidate;
            }
        }

        return null;
    }
}

