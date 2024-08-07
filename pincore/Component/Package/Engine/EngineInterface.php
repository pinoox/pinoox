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


namespace Pinoox\Component\Package\Engine;


use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Translator\Translator;
use RuntimeException;

interface EngineInterface
{
    /**
     * Renders an App.
     *
     * @param string|ReferenceInterface $packageName
     * @return ConfigInterface
     * @throws RuntimeException if the template cannot be rendered
     */
    public function config(string|ReferenceInterface $packageName): ConfigInterface;

    /**
     * Get Lang App.
     *
     * @param string|ReferenceInterface $packageName
     * @return Translator
     * @throws RuntimeException if the template cannot be rendered
     */
    public function lang(string|ReferenceInterface $packageName): Translator;

    /**
     * get routes.
     *
     * @param string|ReferenceInterface $packageName
     * @param string $path
     * @return Router
     * @throws RuntimeException if the template cannot be rendered
     */
    public function router(string|ReferenceInterface $packageName, string $path = ''): Router;

    /**
     * Returns true if the App exists.
     *
     * @param string|ReferenceInterface $packageName
     * @return bool
     * @throws RuntimeException if the engine cannot handle the App name
     */
    public function exists(string|ReferenceInterface $packageName): bool;

    /**
     * Returns true if the App stable.
     *
     * @param string|ReferenceInterface $packageName
     * @return bool
     * @throws RuntimeException if the engine cannot handle the App name
     */
    public function stable(string|ReferenceInterface $packageName): bool;

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
     * @param string $path
     * @return string
     */
    public function path(string|ReferenceInterface $packageName, string $path = ''): string;
}