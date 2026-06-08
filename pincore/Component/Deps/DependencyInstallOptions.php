<?php

namespace Pinoox\Component\Deps;

final readonly class DependencyInstallOptions
{
    public function __construct(
        public bool $production = false,
        public bool $npmCi = true,
        public bool $optimizeAutoloader = true,
    ) {
    }
}
