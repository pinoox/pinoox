<?php

namespace Pinoox\Component\Deps;

use Pinoox\Component\Package\AppComposerVendor;
use Symfony\Component\Process\Process;

final class DependencyInstaller
{
    /**
     * @param callable(string): void|null $onOutput
     */
    public function install(
        DependencyTarget $target,
        DependencyInstallOptions $options,
        ?callable $onOutput = null,
    ): DependencyRunResult {
        return match ($target->type) {
            'composer' => $this->runComposer($target, 'install', $options, $onOutput),
            'npm' => $this->runNpmInstall($target, $options, $onOutput),
            default => $this->failedResult($target, 'install', '(unsupported)', 1, 0.0),
        };
    }

    /**
     * @param callable(string): void|null $onOutput
     */
    public function update(
        DependencyTarget $target,
        DependencyInstallOptions $options,
        ?callable $onOutput = null,
    ): DependencyRunResult {
        return match ($target->type) {
            'composer' => $this->runComposer($target, 'update', $options, $onOutput),
            'npm' => $this->runNpm($target, ['update'], 'npm update', $onOutput),
            default => $this->failedResult($target, 'update', '(unsupported)', 1, 0.0),
        };
    }

    /**
     * @param callable(string): void|null $onOutput
     */
    private function runComposer(
        DependencyTarget $target,
        string $action,
        DependencyInstallOptions $options,
        ?callable $onOutput,
    ): DependencyRunResult {
        $projectRoot = $target->scope === 'platform'
            ? $target->path
            : AppComposerVendor::detectProjectRoot($target->path);

        $composer = AppComposerVendor::resolveComposerBinary($projectRoot);
        $command = $this->buildComposerCommand($composer, $action, $options);

        return $this->runProcess($target, $action, $this->formatCommandLine($command), $command, $target->path, $onOutput);
    }

    /**
     * @param callable(string): void|null $onOutput
     */
    private function runNpmInstall(
        DependencyTarget $target,
        DependencyInstallOptions $options,
        ?callable $onOutput,
    ): DependencyRunResult {
        $lockFile = $target->path . '/package-lock.json';

        if ($options->npmCi && is_file($lockFile)) {
            $command = [$this->npmBinary(), 'ci'];
            $result = $this->runProcess($target, 'install', $this->formatCommandLine($command), $command, $target->path, $onOutput);

            if ($result->succeeded()) {
                return $result;
            }
        }

        $command = [$this->npmBinary(), 'install'];

        return $this->runProcess($target, 'install', $this->formatCommandLine($command), $command, $target->path, $onOutput);
    }

    /**
     * @param list<string> $command
     * @param callable(string): void|null $onOutput
     */
    private function runNpm(
        DependencyTarget $target,
        array $command,
        string $label,
        ?callable $onOutput,
    ): DependencyRunResult {
        $fullCommand = array_merge([$this->npmBinary()], $command);

        return $this->runProcess($target, 'update', $this->formatCommandLine($fullCommand), $fullCommand, $target->path, $onOutput);
    }

    /**
     * @return list<string>
     */
    private function buildComposerCommand(string $composer, string $action, DependencyInstallOptions $options): array
    {
        $flags = [
            $action,
            '--no-interaction',
        ];

        if ($options->production) {
            $flags[] = '--no-dev';
        }

        if ($options->optimizeAutoloader) {
            $flags[] = '--optimize-autoloader';
        }

        if (str_contains($composer, ' ') && str_ends_with($composer, '.phar')) {
            return array_merge(explode(' ', $composer, 2), $flags);
        }

        return array_merge([$composer], $flags);
    }

    /**
     * @param list<string> $command
     * @param callable(string): void|null $onOutput
     */
    private function runProcess(
        DependencyTarget $target,
        string $action,
        string $commandLine,
        array $command,
        string $cwd,
        ?callable $onOutput,
        ?int $timeout = 900,
    ): DependencyRunResult {
        $startedAt = microtime(true);
        $lines = [];

        $process = new Process($command, $cwd, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($onOutput, &$lines) {
            foreach ($this->splitBufferLines($buffer) as $line) {
                $lines[] = $line;
                if ($onOutput !== null) {
                    $onOutput($line);
                }
            }
        });

        $duration = microtime(true) - $startedAt;

        return new DependencyRunResult(
            target: $target,
            action: $action,
            commandLine: $commandLine,
            exitCode: (int) $process->getExitCode(),
            durationSeconds: $duration,
            outputLines: $lines,
        );
    }

    /**
     * @return list<string>
     */
    private function splitBufferLines(string $buffer): array
    {
        $buffer = str_replace(["\r\n", "\r"], "\n", $buffer);
        $parts = explode("\n", $buffer);
        $lines = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $lines[] = rtrim($part, "\r");
        }

        return $lines;
    }

    /**
     * @param list<string> $command
     */
    private function formatCommandLine(array $command): string
    {
        return implode(' ', array_map(static function (string $part): string {
            if ($part === '') {
                return '""';
            }

            if (preg_match('/\s/', $part) === 1) {
                return '"' . str_replace('"', '\\"', $part) . '"';
            }

            return $part;
        }, $command));
    }

    private function failedResult(
        DependencyTarget $target,
        string $action,
        string $commandLine,
        int $exitCode,
        float $duration,
    ): DependencyRunResult {
        return new DependencyRunResult(
            target: $target,
            action: $action,
            commandLine: $commandLine,
            exitCode: $exitCode,
            durationSeconds: $duration,
        );
    }

    private function npmBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
    }
}
