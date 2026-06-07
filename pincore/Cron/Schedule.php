<?php

namespace Pinoox\Cron;

use Closure;

class Schedule
{
    /**
     * @var ScheduledTask[]
     */
    private array $tasks = [];

    public function __construct(private readonly string $package = 'system')
    {
    }

    public function command(string $command): ScheduledTask
    {
        return $this->add(new ScheduledTask($command, 'command', $this->package));
    }

    public function shell(string $command): ScheduledTask
    {
        return $this->add(new ScheduledTask($command, 'shell', $this->package));
    }

    public function call(Closure $callback): ScheduledTask
    {
        return $this->add(new ScheduledTask($callback, 'callback', $this->package));
    }

    /**
     * @return ScheduledTask[]
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    private function add(ScheduledTask $task): ScheduledTask
    {
        $this->tasks[] = $task;

        return $task;
    }
}

