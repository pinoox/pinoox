<?php

namespace Pinoox\Cron;

class ScheduledTaskResult
{
    private function __construct(
        public readonly ScheduledTask $task,
        public readonly string $status,
        public readonly string $output = '',
    ) {
    }

    public static function success(ScheduledTask $task, string $output = ''): self
    {
        return new self($task, 'success', $output);
    }

    public static function failed(ScheduledTask $task, string $output = ''): self
    {
        return new self($task, 'failed', $output);
    }

    public static function skipped(ScheduledTask $task, string $reason): self
    {
        return new self($task, 'skipped', $reason);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}

