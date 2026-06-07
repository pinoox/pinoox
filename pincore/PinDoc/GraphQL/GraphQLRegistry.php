<?php

namespace Pinoox\PinDoc\GraphQL;

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppGraphQLRegistryStore;
use Pinoox\Component\Cache\Store\GraphQLCacheStore;
use Pinoox\PinDoc\AppDocProfile;
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

            $cached = GraphQLCacheStore::loadEntries($package);
            if ($cached !== null && isset($cached[$package])) {
                $entries[$package] = $cached[$package];
                continue;
            }

            $files = $this->routeFiles($manager);
            AppBootstrap::ensure($package);
            $bootGraphql = AppGraphQLRegistryStore::manifests($package);

            if (empty($files) && $bootGraphql === []) {
                continue;
            }

            $config = ['types' => [], 'queries' => [], 'mutations' => [], 'docs' => null];
            foreach ($files as $file) {
                $loaded = require $file;
                if (!is_array($loaded)) {
                    continue;
                }

                $config['types'] = array_merge($config['types'], $loaded['types'] ?? []);
                $config['queries'] = array_merge($config['queries'], $loaded['queries'] ?? []);
                $config['mutations'] = array_merge($config['mutations'], $loaded['mutations'] ?? []);

                if (!empty($loaded['docs'])) {
                    $config['docs'] = $loaded['docs'];
                }
            }

            $config = AppGraphQLRegistryStore::mergeInto($config, $package);

            $entries[$package] = [
                'app' => $package,
                'owner' => AppDocProfile::fromPackage($package)['developer'],
                'app_meta' => AppDocProfile::fromPackage($package),
                'types' => $this->normalizeMap($config['types'] ?? []),
                'queries' => $this->normalizeMap($config['queries'] ?? []),
                'mutations' => $this->normalizeMap($config['mutations'] ?? []),
                'docs' => $config['docs'],
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
                    'flow' => [],
                    'description' => '',
                    'examples' => [],
                ];
                continue;
            }

            if (is_array($definition)) {
                $normalized[$name] = [
                    'class' => $definition['class'] ?? null,
                    'permission' => $definition['permission'] ?? null,
                    'flow' => $this->list($definition['flow'] ?? []),
                    'summary' => (string)($definition['summary'] ?? ''),
                    'description' => (string)($definition['description'] ?? ''),
                    'tag' => (string)($definition['tag'] ?? ''),
                    'deprecated' => (bool)($definition['deprecated'] ?? false),
                    'examples' => $definition['examples'] ?? [],
                    'inputs' => $definition['inputs'] ?? [],
                    'outputs' => $definition['outputs'] ?? [],
                ];
            }
        }

        return $normalized;
    }

    private function routeFiles(object $manager): array
    {
        $files = [];
        $file = $manager->path('routes/graphql.php');

        if (is_file($file)) {
            $files[] = $file;
        }

        return $files;
    }

    private function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? array_values($value) : [$value];
    }
}

