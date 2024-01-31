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


namespace Pinoox\Component\Translator\loader;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader as FileLoaderIlluminate;

class FileLoader extends FileLoaderIlluminate
{
    private string $postfix;

    public function __construct(array|string $path, string $postfix = '')
    {
        $this->postfix = $postfix;
        parent::__construct(new Filesystem(), $path);
    }

    /**
     * @param string $postfix
     */
    public function setPostfix(string $postfix): void
    {
        $this->postfix = $postfix;
    }

    /**
     * @return string
     */
    public function getPostfix(): string
    {
        return $this->postfix;
    }

    /**
     * @param $locale
     * @param $group
     * @param null $namespace
     * @return array|string
     */
    public function load($locale, $group, $namespace = null): array|string
    {
        if ($group !== '*') {
            $group .= $this->postfix;
        }
        return parent::load($locale, $group, $namespace);
    }

    protected function loadPaths(array $paths, $locale, $group): array|string
    {
        return collect($paths)
            ->reduce(function ($output, $path) use ($locale, $group) {
                if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
                    $data = $this->files->getRequire($full);
                    if (is_array($data))
                        $output = array_replace_recursive($output, $this->files->getRequire($full));
                    else
                        $output = $data;
                }
                return $output;
            }, []);
    }
}