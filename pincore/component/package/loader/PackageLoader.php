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


use Pinoox\Component\Validation;

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
        return $this->basePath . '/' . $packageName;
    }

    public function path(string $packageName): string
    {
        return $this->getPathPackage($packageName);
    }

    public function exists(string $packageName): bool
    {
        return is_file($this->getPathPackage($packageName) . '/' . $this->appFile);
    }
}