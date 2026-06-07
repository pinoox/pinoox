<?php

namespace Pinoox\Cron;

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppScheduleRegistryStore;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\Platform;
use Pinoox\Support\SystemConfig;

class ScheduleRegistry
{
    /**
     * @return ScheduledTask[]
     */
    public function all(?string $package = null): array
    {
        $tasks = [];

        if ($package === null || $package === 'system' || Platform::isPackage($package)) {
            $tasks = array_merge($tasks, $this->load('system', SystemConfig::path('system') . '/schedule.php'));
        }

        foreach (AppEngine::all() as $appPackage => $manager) {
            if ($package !== null && $package !== $appPackage) {
                continue;
            }

        $tasks = array_merge($tasks, $this->load($appPackage, $manager->path('schedule.php')));
            $tasks = array_merge($tasks, $this->loadBoot($appPackage));
        }

        return $tasks;
    }

    /**
     * @return ScheduledTask[]
     */
    private function load(string $package, string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $schedule = new Schedule($package);
        $definition = require $file;

        if ($definition instanceof \Closure) {
            $definition($schedule);
        }

        if (is_array($definition)) {
            foreach ($definition as $task) {
                if ($task instanceof ScheduledTask) {
                    $task->package($package);
                    $scheduleTasks[] = $task;
                }
            }

            return $scheduleTasks ?? [];
        }

        return $schedule->tasks();
    }

    /**
     * @return ScheduledTask[]
     */
    private function loadBoot(string $package): array
    {
        AppBootstrap::ensure($package);

        $callbacks = AppScheduleRegistryStore::callbacks($package);
        if ($callbacks === []) {
            return [];
        }

        $schedule = new Schedule($package);
        foreach ($callbacks as $callback) {
            $callback($schedule);
        }

        return $schedule->tasks();
    }
}

