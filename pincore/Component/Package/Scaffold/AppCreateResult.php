<?php

namespace Pinoox\Component\Package\Scaffold;

final class AppCreateResult
{
    /**
     * @param list<string> $nextSteps
     */
    public function __construct(
        public readonly string $package,
        public readonly string $appDir,
        public readonly string $stack,
        public readonly string $profile,
        public readonly ?string $routePath,
        public readonly array $nextSteps,
    ) {
    }
}
