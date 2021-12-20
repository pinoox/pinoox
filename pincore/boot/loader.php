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

namespace pinoox\boot;

define('PINOOX_CORE_PATH', PINOOX_PATH . 'pincore' . DIRECTORY_SEPARATOR);
define('PINOOX_BOOT_PATH', PINOOX_CORE_PATH . 'boot' . DIRECTORY_SEPARATOR);
define('PINOOX_MODEL_PATH', PINOOX_CORE_PATH . 'model' . DIRECTORY_SEPARATOR);
define('PINOOX_COMPONENT_PATH', PINOOX_CORE_PATH . 'component' . DIRECTORY_SEPARATOR);
define('PINOOX_SERVICE_PATH', PINOOX_CORE_PATH . 'service' . DIRECTORY_SEPARATOR);
define('PINOOX_CONFIG_PATH', PINOOX_CORE_PATH . 'config' . DIRECTORY_SEPARATOR);
define('PINOOX_LANG_PATH', PINOOX_CORE_PATH . 'lang' . DIRECTORY_SEPARATOR);
define('PINOOX_PATH_THUMB', 'thumbs/{name}_{size}.{ext}');

use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\Router;
use pinoox\component\Service;

class Loader
{
    private static $className;
    private static $fullClassName;
    private static $arrayCurrentClass = array();
    private static $listAllClass = array();

    public static function boot()
    {
        self::loadServices();
    }


    private static function loadServices()
    {
        $services = Config::get('~service');
        foreach ($services as $service) {
            Service::run($service);
        }
    }

    public static function loadPath($key, $path)
    {
        $path = Dir::path($path);
        if (!is_file($path))
            return false;

        $app = Router::getApp();
        $key = $key.'['.$app.']';
        if (!in_array($key, self::$listAllClass)) {
            self::$listAllClass[] = $key;
            include_once $path;
            return true;
        }
        return false;
    }

    public static function getListClasses()
    {
        return self::$listAllClass;
    }
}