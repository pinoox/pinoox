<?php

namespace Pinoox\Component\Transport;

/**
 * Tracks the host app while a guest app runs inside {@see \Pinoox\Component\Package\App::meeting()}.
 */
final class TransportContext
{
    /** @var list<string|null> */
    private static array $hostStack = [];

    public static function enter(string $hostPackage): void
    {
        self::$hostStack[] = $hostPackage !== '' ? $hostPackage : null;
    }

    public static function leave(): void
    {
        array_pop(self::$hostStack);
    }

    public static function host(): ?string
    {
        if (self::$hostStack === []) {
            return null;
        }

        return self::$hostStack[array_key_last(self::$hostStack)];
    }

    public static function inMeeting(): bool
    {
        return self::$hostStack !== [];
    }
}
