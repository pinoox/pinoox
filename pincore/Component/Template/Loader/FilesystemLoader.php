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


namespace Pinoox\Component\Template\Loader;


use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader as FilesystemLoaderTwig;

class FilesystemLoader extends FilesystemLoaderTwig
{
    private ?string $rootPath;

    public function __construct($paths = [], ?string $rootPath = null)
    {
        $this->rootPath = ($rootPath ?? getcwd()).'/';
        if (null !== $rootPath && false !== ($realPath = realpath($rootPath))) {
            $this->rootPath = $realPath.'/';
        }
        parent::__construct($paths, $rootPath);
    }

    public function addPath(string $path, string $namespace = FilesystemLoaderTwig::MAIN_NAMESPACE): void
    {
        // invalidate the cache
        $this->cache = $this->errorCache = [];

        $checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;
        if (is_dir($checkPath)) {
            $this->paths[$namespace][] = rtrim($path, '/\\');
        }
    }

    private function isAbsolutePath(string $file): bool
    {
        return strspn($file, '/\\', 0, 1)
            || (\strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, \PHP_URL_SCHEME);
    }
}