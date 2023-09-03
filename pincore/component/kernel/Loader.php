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

class Loader
{
    public static function boot(ClassLoader $loader)
    {
        new LoaderManager($loader);
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