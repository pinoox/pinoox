<?php

namespace Pinoox\Component\Deps;

final class DependencyOutputFilter
{
    public function shouldDisplay(string $line, string $type, bool $verbose): bool
    {
        if ($verbose) {
            return trim($line) !== '';
        }

        $trimmed = trim($line);
        if ($trimmed === '') {
            return false;
        }

        $lower = strtolower($trimmed);

        if ($this->looksLikeError($lower)) {
            return true;
        }

        return match ($type) {
            'composer' => $this->isComposerSignalLine($lower),
            'npm' => $this->isNpmSignalLine($lower),
            default => false,
        };
    }

    private function looksLikeError(string $lower): bool
    {
        foreach (['error', 'failed', 'fatal', 'warning', 'npm err', 'composer err', 'could not', 'cannot'] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isComposerSignalLine(string $lower): bool
    {
        foreach ([
            'installing',
            'updating',
            'generating autoload',
            'package operations',
            'memory usage',
            'nothing to install',
            'nothing to update',
            'loading composer',
            'discovering packages',
        ] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return str_starts_with($lower, '- ')
            || str_starts_with($lower, '  - ')
            || preg_match('/^\d+\/\d+\s+\[/', $lower) === 1;
    }

    private function isNpmSignalLine(string $lower): bool
    {
        foreach ([
            'added ',
            'removed ',
            'changed ',
            'packages',
            'audited ',
            'npm warn',
            'npm notice',
            'run `npm audit`',
            'up to date',
            'found 0 vulnerabilities',
        ] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return str_starts_with($lower, 'npm ')
            || str_contains($lower, 'packages in');
    }
}
