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

namespace pinoox\component\kernel;

use Composer\Autoload\ClassLoader;
use pinoox\portal\Config;
use pinoox\component\Service;
use pinoox\portal\Env;

class Loader
{
    public static ClassLoader $loader;

    public static function boot(ClassLoader $loader)
    {
        self::$loader = $loader;
        new LoaderManager($loader);
        Env::register();
        self::loadServices();
    }

    private static function loadServices()
    {
        $services = Config::name('~service')->get();
        foreach ($services as $service) {
            Service::run($service);
        }
    }
}