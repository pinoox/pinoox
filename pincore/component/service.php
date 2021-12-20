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
    /**
     * Package name of app
     *
     * @var null|string
     */
    private static $app = null;

    /**
     * Services information
     *
     * @var array
     */
    private static $services = [];

    /**
     * List of services name
     *
     * @var array
     */
    private static $listServices = [];

    /**
     * Set default app with package name
     *
     * @param string|null $packageName for example "com_pinoox_manager"
     */
    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    /**
     * Get package name of current app
     *
     * @return null|string
     */
    public static function getApp()
    {
        return self::$app;
    }

    /**
     * Get object of current app with service name
     *
     * @param string $service
     * @return mixed|null
     */
    public static function object($service)
    {
        return isset(self::$services[$service]['object'])? self::$services[$service]['object'] : null;
    }

    /**
     * Get names of all services
     *
     * @return array
     */
    public static function all()
    {
        return self::$listServices;
    }

    /**
     * Run service
     *
     * for example "cache.config" => cache is a directory and config is inner service name
     *
     * @param $service
     * @return mixed|null
     */
    public static function run($service)
    {
        return self::runner($service, 'run');
    }

    /**
     * Call method of service
     *
     * @param string $service
     * @param string $status
     * @return mixed|null
     */
    private static function runner($service, $status)
    {
        if (empty($service)) return null;

        $isObject = false;
        if (isset(self::$services[$service]['object'])) {
            $isObject = true;
        } else {
            $classname = self::createServiceName($service);

            if (class_exists($classname)) {
                self::$listServices[] = $service;
                self::$services[$service]['object'] = new $classname;
                $isObject = true;
            }
        }

        if ($isObject) {
            self::$services[$service]['status'] = $status;
            $method = '_' . $status;
            return self::$services[$service]['object']->$method();
        }

        return null;
    }

    /**
     * Generate service name
     *
     * @param $service
     * @return string
     */
    private static function createServiceName($service)
    {
        if (HelperString::firstHas($service, '~') || self::$app === '~') {
            $service = HelperString::firstDelete($service, '~');
            $namespace = "pinoox\\service\\";
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
            $namespace = "pinoox\\app\\" . $app . "\\service\\";
        }

        $services = HelperString::multiExplode(['\\', '>', '/', '.'], $service);
        $serviceName = array_pop($services);
        $services[] = ucfirst($serviceName) . 'Service';
        $classname = implode('\\', $services);
        return $namespace . $classname;
    }

    /**
     * Stop service
     *
     * @param $service
     * @return mixed|null
     */
    public static function stop($service)
    {
        return self::runner($service, 'stop');
    }

    /**
     * Restart service
     *
     * stop and run service
     *
     * @param $service
     * @return mixed|null
     */
    public static function restart($service)
    {
        self::runner($service, 'stop');
        return self::runner($service, 'run');
    }

    /**
     * Get status service
     *
     * @param $service
     * @return string|null
     */
    public static function status($service)
    {
        return isset(self::$services[$service]['status']) ? self::$services[$service]['status'] : null;
    }

}

    
