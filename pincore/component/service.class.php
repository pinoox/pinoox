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

namespace pinoox\component;

class Service
{
    private static $app = null;
    private static $services = [];
    private static $listServices = [];

    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function object($value)
    {
        return isset(self::$services[$value]['object'])? self::$services[$value]['object'] : null;
    }

    public static function all()
    {
        return self::$listServices;
    }

    public static function run($value)
    {
        return self::runner($value, 'run');
    }

    private static function runner($value, $status)
    {
        if (empty($value)) return null;

        $isObject = false;
        if (isset(self::$services[$value]['object'])) {
            $isObject = true;
        } else {
            $classname = self::createServiceName($value);

            if (class_exists($classname)) {
                self::$listServices[] = $value;
                self::$services[$value]['object'] = new $classname;
                $isObject = true;
            }
        }

        if ($isObject) {
            self::$services[$value]['status'] = $status;
            $method = '_' . $status;
            return self::$services[$value]['object']->$method();
        }

        return null;
    }

    private static function createServiceName($value)
    {
        if (HelperString::firstHas($value, '~') || self::$app === '~') {
            $value = HelperString::firstDelete($value, '~');
            $namespace = "pinoox\\service\\";
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
            $namespace = "pinoox\\app\\" . $app . "\\service\\";
        }

        $values = HelperString::multiExplode(['\\', '>', '/', '.'], $value);
        $serviceName = array_pop($values);
        $values[] = ucfirst($serviceName) . 'Service';
        $classname = implode('\\', $values);
        return $namespace . $classname;
    }

    public static function stop($value)
    {
        return self::runner($value, 'stop');
    }

    public static function restart($value)
    {
        self::runner($value, 'stop');
        return self::runner($value, 'run');
    }

    public static function status($value)
    {
        return isset(self::$services[$value]['status']) ? self::$services[$value]['status'] : null;
    }

}

    
