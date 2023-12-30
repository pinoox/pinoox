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
use Pinoox\Portal\Config;
use Pinoox\Component\Service;
use Pinoox\Portal\Env;
use Pinoox\Portal\Dumper;
use Symfony\Component\ErrorHandler\Debug;

class Loader
{
    private static ClassLoader $composer;
    private static string $basePath;

    public static function boot(ClassLoader $loader, string $basePath = ''): void
    {
        self::setBasePath($basePath);
        self::setComposer($loader);
        new LoaderManager($loader);

        Debug::enable();
        Env::register();
        Dumper::register();

        self::loadServices();
    }

    private static function loadServices(): void
    {
        $services = Config::name('~service')->get();
        foreach ($services as $service) {
            Service::run($service);
        }
    }

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = $basePath;
    }

    public static function basePath(): string
    {
        return str_replace('\\','/',self::$basePath);
    }

    public static function composer(): ClassLoader
    {
        return self::$composer;
    }

    public static function setComposer(ClassLoader $composer): void
    {
        self::$composer = $composer;
    }
}