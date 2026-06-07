<?php

namespace Pinoox\Component\Package\Routing;

final class DomainMatch
{
    public function __construct(
        public readonly string  $host,
        public readonly string  $package,
        public readonly string  $path = '/',
        public readonly ?string $subdomain = null,
        public readonly ?string $pattern = null,
    )
    {
    }
}

