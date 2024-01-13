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

namespace Pinoox\Component\Kernel;

use Composer\Autoload\ClassLoader;

class Loader
{
    private static ?ClassLoader $classLoader = null;
    private static ?string $basePath = null;

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = str_replace('\\', '/', $basePath);
    }

    public static function getBasePath(): ?string
    {
        return self::$basePath;
    }

    public static function setClassLoader(ClassLoader $classLoader): void
    {
        self::$classLoader = $classLoader;
    }

    public static function getClassLoader(): ?ClassLoader
    {
        return self::$classLoader;
    }

    public static function set(ClassLoader $classLoader, string $dir): void
    {
        self::setBasePath($dir);
        self::setClassLoader($classLoader);
    }

    public static function init(): void
    {
        if (!empty(self::getClassLoader()))
            return;

        $loaders = ClassLoader::getRegisteredLoaders();
        $vendorDir = array_key_first(ClassLoader::getRegisteredLoaders());
        $classLoader = $loaders[$vendorDir];

        self::setBasePath(dirname($vendorDir));
        self::setClassLoader($classLoader);

        self::manageRegisters();
    }

    private static function manageRegisters()
    {
        new LoaderManager(self::$classLoader);
    }
}