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


namespace pinoox\component\package\engine;


use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\store\Config;
use RuntimeException;

interface EngineInterface
{
    /**
     * Renders an App.
     *
     * @param string|ReferenceInterface $packageName
     * @return Config
     * @throws RuntimeException if the template cannot be rendered
     */
    public function config(string|ReferenceInterface $packageName): Config;

    /**
     * Returns true if the App exists.
     *
     * @param string|ReferenceInterface $packageName
     * @return bool
     * @throws RuntimeException if the engine cannot handle the App name
     */
    public function exists(string|ReferenceInterface $packageName): bool;

    /**
     * Returns true if this class is able to render the given App.
     * @param string|ReferenceInterface $packageName
     * @return bool
     */
    public function supports(string|ReferenceInterface $packageName): bool;

    /**
     * get path app
     *
     * @param string|ReferenceInterface $packageName
     * @return string
     */
    public function path(string|ReferenceInterface $packageName): string;
}