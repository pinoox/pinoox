<?php

namespace Pinoox\Cron;

use DateTimeImmutable;

class ScheduleRunner
{
    public function __construct(private readonly ScheduleRegistry $registry = new ScheduleRegistry())
    {
    }

    /**
     * @return ScheduledTaskResult[]
     */
    public function run(?string $package = null, ?string $name = null, bool $all = false, bool $dryRun = false, ?DateTimeImmutable $date = null): array
    {
        $results = [];

        foreach ($this->registry->all($package) as $task) {
            if ($name !== null && $task->getName() !== $name) {
                continue;
            }

            if (!$all && !$task->isDue($date)) {
                continue;
            }

            $results[] = $task->run($dryRun);
        }

        return $results;
    }
}

