<?php

namespace Pinoox\Component\Package\Pinx;

use Symfony\Component\Finder\Finder;

class PinxFileSelector
{
    /**
     * @param array{
     *     gitignore?: bool,
     *     exclude?: list<string>,
     *     include_themes?: list<string>
     * } $buildConfig
     */
    public function files(string $sourcePath, array $buildConfig): Finder
    {
        $finder = new Finder();
        $finder
            ->in($sourcePath)
            ->files()
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs();

        if (!empty($buildConfig['gitignore'])) {
            $finder->ignoreVCSIgnored(true);
        }

        foreach ($buildConfig['exclude'] ?? [] as $excludePath) {
            if (str_contains($excludePath, '*')) {
                $this->excludeWildcardPaths($finder, $sourcePath, $excludePath);
                continue;
            }

            $absolutePath = rtrim($sourcePath, '/\\') . '/' . ltrim($excludePath, '/\\');
            if (is_dir($absolutePath)) {
                $finder->notPath($excludePath);
            } elseif (is_file($absolutePath)) {
                $finder->notPath($excludePath);
            }
        }

        $this->applyThemeFilter($finder, $sourcePath, $buildConfig['include_themes'] ?? []);

        return $finder;
    }

    /**
     * Collect files from a directory that must ship in the package even when gitignored.
     *
     * @return array<string, string> map of relative path => absolute path
     */
    public function forcedDirectoryFiles(string $sourcePath, string $relativeDir): array
    {
        $absolute = rtrim(str_replace('\\', '/', $sourcePath), '/') . '/' . ltrim(str_replace('\\', '/', $relativeDir), '/');
        if (!is_dir($absolute)) {
            return [];
        }

        $files = [];
        $finder = new Finder();
        $finder
            ->in($absolute)
            ->files()
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs();

        $prefix = trim(str_replace('\\', '/', $relativeDir), '/');

        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }

            $relativePath = $prefix . '/' . str_replace('\\', '/', $file->getRelativePathname());
            $files[$relativePath] = $realPath;
        }

        return $files;
    }

    /**
     * @param array{
     *     gitignore?: bool,
     *     exclude?: list<string>,
     *     include_themes?: list<string>,
     *     always_include?: list<string>
     * } $buildConfig
     * @return array<string, string> map of relative path => absolute path
     */
    public function payloadFiles(string $sourcePath, array $buildConfig): array
    {
        $files = [];

        foreach ($this->files($sourcePath, $buildConfig)->files() as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }

            $files[str_replace('\\', '/', $file->getRelativePathname())] = $realPath;
        }

        foreach ($buildConfig['always_include'] ?? [] as $relativeDir) {
            foreach ($this->forcedDirectoryFiles($sourcePath, $relativeDir) as $relativePath => $realPath) {
                $files[$relativePath] = $realPath;
            }
        }

        return $files;
    }

    /**
     * @param list<string> $includeThemes
     */
    private function applyThemeFilter(Finder $finder, string $sourcePath, array $includeThemes): void
    {
        if ($includeThemes === []) {
            return;
        }

        $themeBasePath = rtrim($sourcePath, '/\\') . '/theme';
        if (!is_dir($themeBasePath)) {
            return;
        }

        $themeFinder = new Finder();
        $themeFinder->in($themeBasePath)->directories()->depth(0);

        foreach ($themeFinder as $dir) {
            $themeName = $dir->getRelativePathname();
            if (!in_array($themeName, $includeThemes, true)) {
                $finder->notPath('theme/' . $themeName);
            }
        }
    }

    private function excludeWildcardPaths(Finder $finder, string $packagePath, string $wildcardPath): void
    {
        $parts = explode('/*', $wildcardPath, 2);
        $baseDir = $parts[0];
        $remainingPath = isset($parts[1]) ? trim($parts[1], '/') : '';
        $baseAbsolute = rtrim($packagePath, '/\\') . '/' . ltrim($baseDir, '/\\');

        if (!is_dir($baseAbsolute)) {
            return;
        }

        $subDirectories = (new Finder())
            ->in($baseAbsolute)
            ->directories()
            ->depth(0)
            ->name('*')
            ->sortByName();

        foreach ($subDirectories as $dir) {
            $actualPath = $dir->getRealPath() . ($remainingPath !== '' ? '/' . $remainingPath : '');

            if (!is_dir($actualPath) && !is_file($actualPath)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($actualPath, strlen(rtrim($packagePath, '/\\')) + 1));
            $finder->notPath($relativePath);
        }
    }
}

