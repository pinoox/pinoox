<?php

namespace Pinoox\Component\Router\Action;

use Pinoox\Portal\App\AppEngine;

class ActionCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(?string $package = null): array
    {
        ActionRegistry::reset();

        $rows = [];
        foreach (AppEngine::all() as $packageName => $manager) {
            if ($package !== null && $package !== $packageName) {
                continue;
            }

            try {
                AppEngine::router($packageName, '/');
            } catch (\Throwable) {
                continue;
            }

            foreach (ActionRegistry::all($packageName) as $definition) {
                $row = $definition->toArray();
                $row['package'] = $packageName;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function validate(?string $package = null, bool $reportUnused = true): array
    {
        ActionRegistry::reset();
        $errors = [];
        $validator = new ActionValidator();

        foreach (AppEngine::all() as $packageName => $manager) {
            if ($package !== null && $package !== $packageName) {
                continue;
            }

            try {
                $router = AppEngine::router($packageName, '/');
            } catch (\Throwable $e) {
                $errors[] = sprintf('Package "%s": %s', $packageName, $e->getMessage());
                continue;
            }

            $errors = array_merge($errors, $validator->validate($router, $packageName, $reportUnused));
        }

        return array_values(array_unique($errors));
    }
}

