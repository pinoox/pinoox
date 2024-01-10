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


interface LoaderInterface
{
    /**
     * get path package
     *
     * @param string $packageName
     * @return string
     * @throws \Exception
     */
    public function path(string $packageName): string;

    /**
     * exists package
     *
     * @param string $packageName
     * @return bool
     */
    public function exists(string $packageName): bool;
}