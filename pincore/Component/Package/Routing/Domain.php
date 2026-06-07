<?php

namespace Pinoox\Component\Package\Routing;

final class Domain
{
    private static ?DomainMatcher $matcher = null;

    public static function matcher(): DomainMatcher
    {
        return self::$matcher ??= new DomainMatcher();
    }

    public static function reset(): void
    {
        DomainMatcher::reset();
        self::$matcher = null;
    }

    /**
     * @internal test helper
     */
    public static function useConfig(array $config): void
    {
        DomainMatcher::useConfig($config);
        self::$matcher = null;
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        return self::matcher()->config($key, $default);
    }

    public static function defaultHost(): ?string
    {
        return self::matcher()->defaultHost();
    }

    public static function hostMap(): array
    {
        return self::matcher()->hostMap();
    }

    public static function match(?string $host): ?DomainMatch
    {
        return self::matcher()->match($host);
    }

    /**
     * Unmapped hosts use default domain routing (path routes).
     * Only hosts explicitly listed in domain.config.php are dedicated.
     */
    public static function isDefaultHost(?string $host): bool
    {
        return self::matcher()->isDefaultHost($host);
    }

    /**
     * True when the host equals the configured canonical default domain.
     */
    public static function isCanonicalDefaultHost(?string $host): bool
    {
        return self::matcher()->isCanonicalDefaultHost($host);
    }

    public static function normalizeHost(string $host): string
    {
        return DomainMatcher::normalizeHost($host);
    }
}

