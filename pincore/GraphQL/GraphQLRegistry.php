<?php

namespace Pinoox\GraphQL;

use Pinoox\Portal\App\AppEngine;

class GraphQLRegistry
{
    public function all(?string $app = null): array
    {
        $entries = [];

        foreach (AppEngine::all() as $package => $manager) {
            if ($app !== null && $package !== $app) {
                continue;
            }

            $file = $manager->path('graphql/graphql.php');
            if (!is_file($file)) {
                continue;
            }

            $config = require $file;
            if (!is_array($config)) {
                continue;
            }

            $entries[$package] = [
                'app' => $package,
                'owner' => $manager->config()->get('developer') ?: $manager->config()->get('name') ?: $package,
                'types' => $this->normalizeMap($config['types'] ?? []),
                'queries' => $this->normalizeMap($config['queries'] ?? []),
                'mutations' => $this->normalizeMap($config['mutations'] ?? []),
            ];
        }

        return $entries;
    }

    private function normalizeMap(array $items): array
    {
        $normalized = [];

        foreach ($items as $name => $definition) {
            if (is_string($definition)) {
                $normalized[$name] = [
                    'class' => $definition,
                    'permission' => null,
                    'middleware' => [],
                    'description' => '',
                    'examples' => [],
                ];
                continue;
            }

            if (is_array($definition)) {
                $normalized[$name] = [
                    'class' => $definition['class'] ?? null,
                    'permission' => $definition['permission'] ?? null,
                    'middleware' => is_array($definition['middleware'] ?? null) ? $definition['middleware'] : array_filter([$definition['middleware'] ?? null]),
                    'description' => (string)($definition['description'] ?? ''),
                    'examples' => $definition['examples'] ?? [],
                    'inputs' => $definition['inputs'] ?? [],
                    'outputs' => $definition['outputs'] ?? [],
                ];
            }
        }

        return $normalized;
    }
}
