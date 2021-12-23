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

use Closure;

class Cache
{
    /**
     * Data cache files
     *
     * @var array
     */
    private static $data = [];

    /**
     * Package name of app
     *
     * @var string|null
     */
    private static $app = null;

    /**
     * Store data from cache init
     *
     * @var array|null
     */
    private static $initData;

    /**
     * Set current app with package name
     *
     * @param string $packageName
     */
    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    /**
     * Init data if not exists cache file
     *
     * @param string $cache_name
     * @param Closure|array $data
     * @param int|null $clean_hour
     */
    public static function init($cache_name, $data, $clean_hour = null)
    {
        $name = $cache_name;
        $pointer = 'data';
        if (HelperString::firstHas($cache_name, '!')) {
            $cache_name = HelperString::firstDelete($cache_name, '!');
            $pointer = 'info';
        }

        $isApp = (HelperString::firstHas($cache_name, '~')) ? false : true;
        $cache_name = HelperString::firstDelete($cache_name, '~');

        if ($isApp) {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        } else {
            $app = '~';
        }

        self::$initData[$app][$cache_name][$pointer] = $data;
        if (!empty($clean_hour) && is_numeric($clean_hour))
            self::clean($name, $clean_hour);
    }

    /**
     * Remove cache file
     *
     * @param string $name
     * @param int $hour
     */
    public static function clean($name, $hour = 0)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                self::clean($n, $hour);
            }
            return;
        }

        if ($hour > 0) {
            $name = HelperString::firstDelete($name, '!');
            $name = '!' . $name;
            $info = self::get($name);
            $time = $info['time'] + ($hour * 60 * 60);
            if ($time < time())
                self::clean($name);
            return;
        }

        $filename = str_replace(['/', '\\'], '>', $name);
        $filename = HelperString::firstDelete($filename, '!');

        if (HelperString::firstHas($filename, '~')) {
            $filename = HelperString::firstDelete($filename, '~');
            $file = Dir::path('~pincore/pinker/cache/' . $filename . '.cache.php');
            $app = '~';
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
            $file = Dir::path('pinker/cache/' . $filename . '.cache.php', $app);
        }

        unset(self::$data[$app][$filename]);
        File::remove_file($file);
    }

    /**
     * Get data from cache data
     *
     * @param string $value
     * @return array|mixed|null
     */
    public static function get($value)
    {
        if (HelperString::firstHas($value, '!')) {
            $value = HelperString::firstDelete($value, '!');
            return self::pull($value, true);
        } else {
            return self::pull($value);
        }
    }
    
    /**
     * Get target data from cache
     *
     * @param string $pointer
     * @param string $key
     * @return mixed|null
     */
    public static function getLinear($pointer, $key)
    {
        $data = self::get($pointer);
        return isset($data[$key])? $data[$key] : null;
    }

    /**
     * Read info on the cache data
     *
     * @param string $value
     * @param bool $isInfo
     * @return array|mixed|null
     */
    private static function pull($value, $isInfo = false)
    {
        if (empty($value)) return null;
        $pointer = $isInfo ? 'info' : 'data';
        $info = explode('.', $value);
        $filename = array_shift($info);
        $filename = str_replace(['/', '\\'], '>', $filename);

        if (HelperString::firstHas($filename, '~')) {
            $filename = HelperString::firstDelete($filename, '~');
            $app = '~';
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        }


        self::loadFile($app, $filename);
        return self::result($app, $filename, $info, $pointer);
    }

    /**
     * load values from the cache file
     *
     * @param string $app
     * @param string $filename
     */
    private static function loadFile($app, $filename)
    {
        if (isset(self::$data[$app][$filename])) return;

        if ($app !== '~') {
            $file = Dir::path('pinker/cache/' . $filename . '.cache.php', $app);
        } else {
            $file = Dir::path('~pincore/pinker/cache/' . $filename . '.cache.php');
        }


        if (is_file($file)) {
            self::$data[$app][$filename] = self::includeFile($file);
        } else {
            self::$data[$app][$filename] = self::loadInitData($app, $filename);
            if ($app === '~')
                $filename = '~' . $filename;
            self::save($filename);
        }

        self::setDefaultInfo($app, $filename);
    }

    /**
     * include cache file
     *
     * @param string $file
     * @return array|mixed|null
     */
    private static function includeFile($file)
    {
        return (include $file);
    }

    /**
     * Get array from data init
     *
     * @param string $app
     * @param string $filename
     * @return array
     */
    private static function loadInitData($app, $filename)
    {
        $data = (isset(self::$initData[$app][$filename]['data'])) ? self::$initData[$app][$filename]['data'] : [];

        if (is_callable($data)) {
            $data = $data();
        }

        $info = (isset(self::$initData[$app][$filename]['info'])) ? self::$initData[$app][$filename]['info'] : [];
        if (is_callable($info)) {
            $info = $info();
        }

        return ['data' => $data, 'info' => $info];
    }

    /**
     * Save data on the cache file
     *
     * @param string $name
     */
    private static function save($name)
    {
        if (empty($name) || HelperString::has($name, '.')) return;

        HelperString::firstDelete($name, '!');
        $isApp = (HelperString::firstHas($name, '~')) ? false : true;

        if ($isApp) {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        } else {
            $app = '~';
        }
        $name = HelperString::firstDelete($name, '~');

        self::setDefaultInfo($app, $name, true);

        $data = (isset(self::$data[$app][$name]['data'])) ? self::$data[$app][$name]['data'] : [];
        $info = (isset(self::$data[$app][$name]['info'])) ? self::$data[$app][$name]['info'] : [];

        $arr = ['info' => $info, 'data' => $data];
        $filename = $name . '.cache.php';

        if ($isApp) {
            $file = Dir::path('pinker/cache/' . $filename, self::$app);
        } else {
            $file = Dir::path('~pincore/pinker/cache/' . $filename);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox cache file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . var_export($arr, true) . ";\n\n//end of cache";

        File::generate($file, $data_for_save);
    }

    /**
     * Set data on array cache data with index info
     *
     * @param string $app
     * @param string $filename
     * @param bool $isSave
     */
    private static function setDefaultInfo($app, $filename, $isSave = false)
    {
        self::readyInfo($app, $filename);

        if ($isSave || !isset(self::$data[$app][$filename]['info']['time'])) {
            $all_info = self::defaultInfo($app, $filename);
            foreach ($all_info as $key => $value) {
                self::$data[$app][$filename]['info'][$key] = $value;
            }
        }
    }

    /**
     * Preparation data on array cache file with index info
     *
     * @param string $app
     * @param string $filename
     */
    private static function readyInfo($app, $filename)
    {
        if (!isset(self::$data[$app][$filename]['info']))
            self::$data[$app][$filename]['info'] = [];

        else if (!is_array(self::$data[$app][$filename]['info']))
            self::$data[$app][$filename]['info'] = [self::$data[$app][$filename]['info']];
    }

    /**
     * Values default array cache data with index info
     *
     * @param string $app
     * @param string $filename
     * @return array
     */
    private static function defaultInfo($app, $filename)
    {
        return [
            'time' => time(),
        ];
    }

    /**
     * Result request get data
     *
     * @param string $app
     * @param string $filename
     * @param array $info
     * @param string $pointer
     * @param bool $isTemp
     * @return array|mixed|null
     */
    private static function result($app, $filename, $info, $pointer, $isTemp = false)
    {
        $result = self::$data[$app][$filename];
        $result = (isset($result[$pointer])) ? $result[$pointer] : [];
        foreach ($info as $value) {
            if (isset($result[$value])) {
                $result = $result[$value];
            } else {
                $result = null;
                break;
            }
        }

        return $result;
    }

    /**
     * Set data on the cache data
     *
     * @param string $key
     * @param mixed $value
     */
    private static function set($key, $value)
    {
        if (HelperString::firstHas($key, '!')) {
            $key = HelperString::firstDelete($key, '!');
            self::push($key, $value, 'set', true);
        } else {
            self::push($key, $value, 'set');
        }
    }

    /**
     * Write info on the cache data
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param bool $isInfo
     */
    private static function push($key, $value, $type = 'add', $isInfo = false)
    {
        $pointer = $isInfo ? 'info' : 'data';
        $isApp = (HelperString::firstHas($key, '~')) ? false : true;
        $key = HelperString::firstDelete($key, '~');
        $keys = explode('.', $key);
        $filename = $keys[0];
        $filename = str_replace(['/', '\\'], '>', $filename);

        if ($isApp) {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        } else {
            $app = '~';
        }

        self::loadFile($app, $filename);
        $temp = &self::$data[$app];

        $countKeys = count($keys) - 1;
        $key = null;
        for ($i = 0; $i <= $countKeys; $i++) {
            $key = $keys[$i];
            if (!isset($temp[$key]) || !is_array($temp[$key])) {
                if ($i == 0)
                    $temp[$key] = ['info' => [], 'data' => []];
            }
            if (($i == 0) && !isset($temp[$key][$pointer]))
                $temp[$key][$pointer] = [];
            if (($i != $countKeys)) {
                if ($i == 0)
                    $temp = &$temp[$key][$pointer];
                else
                    $temp = &$temp[$key];
            }
        }

        if ($countKeys == 0) {
            if ($type == 'add') {
                if (!isset($temp[$key][$pointer])) {
                    $temp[$key][$pointer] = [$value];
                } else {
                    if (!is_array($temp[$key][$pointer]))
                        $temp[$key][$pointer] = [$temp[$key][$pointer]];
                    $temp[$key][$pointer][] = $value;
                }
            } else if ($type == 'set') {
                $temp[$key][$pointer] = $value;
            }
        } else {
            if ($type == 'add') {
                if (!isset($temp[$key])) {
                    $temp[$key] = [$value];
                } else {
                    if (!is_array($temp[$key]))
                        $temp[$key] = [$temp[$key]];
                    $temp[$key][] = $value;
                }
            } else if ($type == 'set') {
                $temp[$key] = $value;
            }
        }

    }

    /**
     * add data on the cache data
     *
     * @param string $key
     * @param mixed $value
     */
    private static function add($key, $value)
    {
        if (HelperString::firstHas($key, '!')) {
            $key = HelperString::firstDelete($key, '!');
            self::push($key, $value, 'add', true);
        } else {
            self::push($key, $value, 'add');
        }
    }


}

    
