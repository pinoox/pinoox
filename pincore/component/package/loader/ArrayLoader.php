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


namespace pinoox\component\package\loader;


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
        private array $packages = []
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
        return is_file($this->packages[$packageName] . DIRECTORY_SEPARATOR . $this->appFile);
    }

    /**
     * add package
     *
     * @param string $packageName
     * @param string $path
     */
    public function add(string $packageName, string $path): void
    {
        $this->packages[$packageName] = $path;
    }
}