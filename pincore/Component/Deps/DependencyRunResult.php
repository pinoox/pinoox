<?php

namespace Pinoox\Component\Deps;

final readonly class DependencyRunResult
{
    /**
     * @param list<string> $outputLines
     */
    public function __construct(
        public DependencyTarget $target,
        public string $action,
        public string $commandLine,
        public int $exitCode,
        public float $durationSeconds,
        public array $outputLines = [],
    ) {
    }

    public function succeeded(): bool
    {
        return $this->exitCode === 0;
    }
}
