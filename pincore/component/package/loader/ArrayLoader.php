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


namespace Pinoox\Component\Package\Loader;


final class ArrayLoader implements LoaderInterface
{
    /**
     * ArrayLoader constructor.
     *
     * @param string $appFile
     * @param array $packages
     */
    public function __construct(
        private string $appFile,
        private array  $packages = []
    )
    {
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $packageName): bool
    {
        return isset($this->packages[$packageName]) && $this->checkExistsFile($packageName);
    }


    /**
     *  {@inheritDoc}
     */
    public function path(string $packageName): string
    {
        return $this->packages[$packageName];
    }

    /**
     * Check exists file
     *
     * @param string $packageName
     * @return bool
     */
    private function checkExistsFile(string $packageName): bool
    {
        return is_file($this->packages[$packageName] . '/' . $this->appFile);
    }

    /**
     * add package
     *
     * @param string $packageName
     * @param string $path
     */
    public function add(string $packageName, string $path): void
    {
        $path = str_replace('\\', '/', $path);
        $this->packages[$packageName] = $path;
    }

    public function getPackages()
    {
        return $this->packages;
    }
}