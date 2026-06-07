<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Cache\Store;

use Pinoox\Component\Cache\AppCacheFingerprint;
use Pinoox\Component\Cache\AppCacheManifest;
use Pinoox\Component\Cache\AppCachePath;
use Pinoox\Component\Cache\CacheStoreInterface;
use Pinoox\Component\Template\Engine\TwigEngine;
use Pinoox\Component\Template\Parser\TemplateNameParser;
use Pinoox\Component\Template\Theme\ThemeStack;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TwigCacheStore implements CacheStoreInterface
{
    public function name(): string
    {
        return 'twig';
    }

    public function path(string $package): string
    {
        return AppCachePath::twig($package);
    }

    public function isFresh(string $package): bool
    {
        return AppCacheFingerprint::isFresh($package, $this->name(), $this->sourceFiles($package));
    }

    public function build(string $package): bool
    {
        $themePaths = ThemeStack::paths($package);

        if ($themePaths === []) {
            return false;
        }

        $cacheDir = $this->path($package);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $engine = new TwigEngine(new TemplateNameParser(), $themePaths, [
            'cache' => $cacheDir,
            'auto_reload' => false,
        ]);

        foreach ($this->twigTemplates($themePaths) as $template) {
            try {
                $engine->template->load($template);
            } catch (\Throwable) {
            }
        }

        AppCacheManifest::touchStore($package, $this->name(), [
            'checksum' => AppCacheFingerprint::files($this->sourceFiles($package)),
            'theme' => ThemeStack::activeName($package),
        ]);

        return true;
    }

    public function clear(string $package): void
    {
        $this->removeDirectory($this->path($package));
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(string $package): array
    {
        return $this->twigTemplates(ThemeStack::paths($package));
    }

    /**
     * @param list<string> $themePaths
     * @return list<string>
     */
    private function twigTemplates(array $themePaths): array
    {
        $files = [];

        foreach ($themePaths as $themePath) {
            if (!is_dir($themePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($themePath, \FilesystemIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $name = $file->getFilename();
                if (str_ends_with($name, '.twig') || str_ends_with($name, '.twig.php')) {
                    $files[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        }

        sort($files);

        return $files;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}

