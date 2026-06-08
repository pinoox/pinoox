<?php

namespace Pinoox\Component\Deps;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;

final class DependencyScanner
{
    public function projectRoot(): string
    {
        return SystemConfig::rootPath();
    }

    public function appsPath(): string
    {
        return SystemConfig::path('apps');
    }

    /**
     * @return list<DependencyTarget>
     */
    public function discover(
        string $scope = 'all',
        ?string $themeName = null,
        bool $allThemes = false,
        ?string $typeFilter = null,
    ): array {
        $targets = [];

        if ($scope === 'all' || $scope === 'platform') {
            $targets = array_merge($targets, $this->platformTargets($typeFilter));
        }

        if ($scope === 'all') {
            foreach (AppEngine::all() as $package => $manager) {
                if (!$manager->exists()) {
                    continue;
                }

                $targets = array_merge(
                    $targets,
                    $this->appTargets((string) $package, $themeName, $allThemes, $typeFilter),
                );
            }
        } elseif ($scope !== 'platform') {
            $targets = array_merge(
                $targets,
                $this->appTargets($scope, $themeName, $allThemes, $typeFilter),
            );
        }

        return $this->uniqueTargets($targets);
    }

    /**
     * @return list<DependencyTarget>
     */
    private function platformTargets(?string $typeFilter): array
    {
        if ($typeFilter !== null && $typeFilter !== 'composer') {
            return [];
        }

        $root = $this->projectRoot();

        if (!is_file($root . '/composer.json')) {
            return [];
        }

        return [
            new DependencyTarget(
                type: 'composer',
                scope: 'platform',
                path: $root,
                label: 'Project root (composer)',
            ),
        ];
    }

    /**
     * @return list<DependencyTarget>
     */
    private function appTargets(
        string $package,
        ?string $themeName,
        bool $allThemes,
        ?string $typeFilter,
    ): array {
        if (!AppEngine::exists($package)) {
            return [];
        }

        $targets = [];
        $appPath = rtrim(str_replace('\\', '/', AppEngine::path($package)), '/');

        if (($typeFilter === null || $typeFilter === 'composer') && is_file($appPath . '/composer.json')) {
            $targets[] = new DependencyTarget(
                type: 'composer',
                scope: $package,
                path: $appPath,
                label: $package . ' (composer)',
            );
        }

        if ($typeFilter === null || $typeFilter === 'npm') {
            foreach ($this->themePaths($package, $themeName, $allThemes) as $themePath) {
                if (!is_file($themePath . '/package.json')) {
                    continue;
                }

                $themeFolder = basename($themePath);
                $targets[] = new DependencyTarget(
                    type: 'npm',
                    scope: $package,
                    path: $themePath,
                    label: $package . ' / theme/' . $themeFolder . ' (npm)',
                );
            }
        }

        return $targets;
    }

    /**
     * @return list<string>
     */
    private function themePaths(string $package, ?string $themeName, bool $allThemes): array
    {
        $appPath = rtrim(str_replace('\\', '/', AppEngine::path($package)), '/');
        $themesRoot = $appPath . '/theme';

        if ($allThemes) {
            return $this->discoverThemeDirectories($themesRoot);
        }

        $theme = $themeName ?: (string) AppEngine::config($package)->get('theme', 'default');

        return [rtrim($themesRoot . '/' . $theme, '/')];
    }

    /**
     * @return list<string>
     */
    private function discoverThemeDirectories(string $themesRoot): array
    {
        if (!is_dir($themesRoot)) {
            return [];
        }

        $paths = [];

        foreach (scandir($themesRoot) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $themesRoot . '/' . $entry;
            if (is_dir($path)) {
                $paths[] = rtrim(str_replace('\\', '/', $path), '/');
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * @param list<DependencyTarget> $targets
     * @return list<DependencyTarget>
     */
    private function uniqueTargets(array $targets): array
    {
        $seen = [];
        $unique = [];

        foreach ($targets as $target) {
            $key = $target->type . ':' . $target->path;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $target;
        }

        return $unique;
    }
}
