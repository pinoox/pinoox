<?php

namespace Pinoox\Cron;

use DateTimeImmutable;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Process\Process;

class ScheduledTask
{
    private string $expression = '* * * * *';
    private ?string $taskName = null;
    private ?string $taskDescription = null;
    private array $taskFlows = [];
    private bool $taskLock = false;
    private mixed $taskAction;
    private string $taskType;
    private string $taskPackage;

    public function __construct(mixed $taskAction, string $taskType, string $taskPackage = 'system')
    {
        $this->taskAction = $taskAction;
        $this->taskType = $taskType;
        $this->taskPackage = $taskPackage;
    }

    public function package(string $package): self
    {
        $this->taskPackage = $package;
        return $this;
    }

    public function name(string $name): self
    {
        $this->taskName = $name;
        return $this;
    }

    public function description(string $description): self
    {
        $this->taskDescription = $description;
        return $this;
    }

    public function flow(array|string $flow): self
    {
        $items = is_array($flow) ? $flow : [$flow];
        $this->taskFlows = array_values(array_unique(array_merge($this->taskFlows, $items)));
        return $this;
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    public function everyMinute(): self { return $this->cron('* * * * *'); }

    public function everyFiveMinutes(): self { return $this->cron('*/5 * * * *'); }

    public function everyTenMinutes(): self { return $this->cron('*/10 * * * *'); }

    public function everyFifteenMinutes(): self { return $this->cron('*/15 * * * *'); }

    public function everyThirtyMinutes(): self { return $this->cron('*/30 * * * *'); }

    public function hourly(): self { return $this->cron('0 * * * *'); }

    public function daily(): self { return $this->dailyAt('00:00'); }

    public function weekly(): self { return $this->cron('0 0 * * 0'); }

    public function monthly(): self { return $this->cron('0 0 1 * *'); }

    public function dailyAt(string $time): self
    {
        [$hour, $minute] = array_map('intval', explode(':', $time, 2));
        return $this->cron(sprintf('%d %d * * *', $minute, $hour));
    }

    public function withoutOverlapping(): self
    {
        $this->taskLock = true;
        return $this;
    }

    public function isDue(?DateTimeImmutable $date = null): bool
    {
        return (new CronExpression($this->expression))->isDue($date);
    }

    public function run(bool $dryRun = false): ScheduledTaskResult
    {
        if ($this->taskLock && $this->isLocked()) {
            return ScheduledTaskResult::skipped($this, 'Task is locked');
        }

        if ($dryRun) {
            return ScheduledTaskResult::skipped($this, 'Dry run');
        }

        $this->lock();

        if ($this->taskAction instanceof \Closure) {
            call_user_func($this->taskAction);
            $this->unlock();

            return ScheduledTaskResult::success($this);
        }

        $process = $this->makeProcess();
        $process->run();
        $this->unlock();

        if ($process->isSuccessful()) {
            return ScheduledTaskResult::success($this, $process->getOutput());
        }

        return ScheduledTaskResult::failed($this, $process->getErrorOutput() ?: $process->getOutput());
    }

    public function id(): string
    {
        return sha1($this->taskPackage . '|' . $this->getName() . '|' . $this->expression . '|' . $this->taskType);
    }

    public function getName(): string
    {
        if ($this->taskName !== null) {
            return $this->taskName;
        }

        $action = is_string($this->taskAction) ? $this->taskAction : 'callback';

        return $this->taskType . ':' . $action;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'package' => $this->taskPackage,
            'name' => $this->getName(),
            'description' => $this->taskDescription,
            'expression' => $this->expression,
            'type' => $this->taskType,
            'action' => is_string($this->taskAction) ? $this->taskAction : 'callback',
            'flow' => $this->taskFlows,
            'without_overlapping' => $this->taskLock,
        ];
    }

    private function makeProcess(): Process
    {
        if ($this->taskType === 'command') {
            $parts = preg_split('/\s+/', trim((string)$this->taskAction), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            return new Process(array_merge([PHP_BINARY, 'pinoox'], $parts), SystemConfig::path('~'));
        }

        return Process::fromShellCommandline((string)$this->taskAction, SystemConfig::path('~'));
    }

    private function lockPath(): string
    {
        return SystemConfig::path('storage') . '/schedule/' . $this->id() . '.lock';
    }

    private function isLocked(): bool
    {
        return is_file($this->lockPath());
    }

    private function lock(): void
    {
        if (!$this->taskLock) {
            return;
        }

        $dir = dirname($this->lockPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->lockPath(), (string)time());
    }

    private function unlock(): void
    {
        if ($this->taskLock && is_file($this->lockPath())) {
            @unlink($this->lockPath());
        }
    }
}

