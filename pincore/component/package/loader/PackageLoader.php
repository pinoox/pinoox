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


use pinoox\component\Validation;

final class PackageLoader implements LoaderInterface
{
    public function __construct(
        private string $appFile,
        private string $basePath = ''
    )
    {
    }

    private function getPathPackage($packageName): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $packageName;
    }

    public function path(string $packageName): string
    {
        return $this->getPathPackage($packageName);
    }

    public function exists(string $packageName): bool
    {
        return is_file($this->getPathPackage($packageName) . DIRECTORY_SEPARATOR . $this->appFile);
    }
}