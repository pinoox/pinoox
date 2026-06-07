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
