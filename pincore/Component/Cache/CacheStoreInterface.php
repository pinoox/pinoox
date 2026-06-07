<?php

namespace Pinoox\Component\Cache;

interface CacheStoreInterface
{
    public function name(): string;

    public function build(string $package): bool;

    public function clear(string $package): void;

    public function isFresh(string $package): bool;

    public function path(string $package): string;
}

