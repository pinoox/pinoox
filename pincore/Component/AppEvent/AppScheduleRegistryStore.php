<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Cron\Schedule;

class AppScheduleRegistryStore
{
    /** @var array<string, list<callable(Schedule): void>> */

    private static array $callbacks = [];

    public static function absorb(string $package, AppRegisterCollector $collector): void
    {
        if ($collector->schedules === []) {
            return;
        }

        self::$callbacks[$package] = array_merge(self::$callbacks[$package] ?? [], $collector->schedules);
    }

    /**
     * @return list<callable(Schedule): void>
     */
    public static function callbacks(string $package): array
    {
        return self::$callbacks[$package] ?? [];
    }
}

