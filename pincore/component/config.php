<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\component;

class Config
{
    /**
     * Data config
     *
     * @var array
     */
    private static $data = [];

    /**
     * Temp data config
     *
     * @var array
     */
    private static $tempData = [];

    /**
     * Package name of app
     *
     * @var string|null
     */
    private static $app = null;

    /**
     * Change current app with package name
     *
     * @param string $packageName
     */
    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    /**
     * Set target data in config
     *
     * @param string $pointer
     * @param string $key
     * @param mixed $value
     */
    public static function setLinear($pointer, $key, $value)
    {
        $data = self::get($pointer);
        $data = is_array($data) ? $data : [];
        $data[$key] = $value;
        self::set($pointer, $data);
    }

    /**
     * Get data from config
     *
     * @param string $value
     * @return mixed|null
     */
    public static function get($value)
    {
        if (empty($value)) return null;

        $info = explode('.', $value);
        $filename = array_shift($info);
        $filename = str_replace(['/', '\\'], '>', $filename);

        if (HelperString::firstHas($filename, '~')) {
            $filename = HelperString::firstDelete($filename, '~');
            $app = '~';
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        }

        if (!isset(self::$data[$app][$filename])) {
            self::$data[$app][$filename] = self::loadFile($app, $filename);
        }

        return self::result($app, $filename, $info);
    }

    /**
     * Load config file
     *
     * @param $app
     * @param $filename
     * @return mixed|null
     */
    private static function loadFile($app, $filename)
    {
        if ($app !== '~')
            $file = Dir::path('pinker/config/' . $filename . '.config.php', $app);
        else
            $file = Dir::path('~pincore/pinker/config/' . $filename . '.config.php');

        if (!is_file($file)) {
            self::initFile($file, $app, $filename);
        }

        if (is_file($file)) {
            return (include $file);
        }

        return null;
    }

    /**
     * init config file
     *
     * @param $file
     * @param $app
     * @param $filename
     */
    private static function initFile($file, $app, $filename)
    {
        if ($app !== '~')
            $f = Dir::path('config/' . $filename . '.config.php', $app);
        else
            $f = Dir::path('~pincore/config/' . $filename . '.config.php');

        if (is_file($f))
            File::copy($f, $file);
    }

    /**
     * Result request get data
     *
     * @param string $app
     * @param string $filename
     * @param array $info
     * @param bool $isTemp
     * @return mixed|null
     */
    private static function result($app, $filename, $info, $isTemp = false)
    {
        $result = ($isTemp) ? self::$tempData[$app][$filename] : self::$data[$app][$filename];
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
     * Set data in config
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        self::push($key, $value, 'set');
    }

    /**
     * Write data in config
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     */
    private static function push($key, $value, $type = 'add')
    {
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

        if (isset(self::$data[$app][$filename]) && $type == 'reset')
            unset(self::$data[$app][$filename]);

        if ($type == 'reset') return;

        if (!isset(self::$data[$app][$filename])) {

            self::$data[$app][$filename] = self::loadFile($app, $filename);
        }

        $temp = &self::$data[$app];

        $countKeys = count($keys) - 1;
        $key = null;
        for ($i = 0; $i <= $countKeys; $i++) {
            $key = $keys[$i];
            if (($i != $countKeys)) {
                if (!isset($temp[$key]) || !is_array($temp[$key]))
                    $temp[$key] = [];

                $temp = &$temp[$key];
            }
        }

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
        } else if ($type == 'del') {
            unset($temp[$key]);
        }
    }

    /**
     * Get target data from config
     *
     * @param string $pointer
     * @param string $key
     * @return mixed|null
     */
    public static function getLinear($pointer, $key)
    {
        $data = self::get($pointer);
        return isset($data[$key]) ? $data[$key] : null;
    }

    /**
     * Remove data in config
     *
     * @param string $key
     */
    public static function remove($key)
    {
        self::push($key, null, 'del');
    }

    /**
     * bake data in config
     *
     * @param string $key
     */
    public static function bake($filename)
    {
        self::reset($filename);
        $filename = str_replace(['/', '\\'], '>', $filename);

        if (HelperString::firstHas($filename, '~')) {
            $filename = HelperString::firstDelete($filename, '~');
            $app = '~';
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        }

        if ($app !== '~')
            $file = Dir::path('pinker/config/' . $filename . '.config.php', $app);
        else
            $file = Dir::path('~pincore/pinker/config/' . $filename . '.config.php');

        self::initFile($file, $app, $filename);
    }

    /**
     * Remove target data in config
     *
     * @param string $pointer
     * @param string $key
     */
    public static function removeLinear($pointer, $key)
    {
        $data = self::get($pointer);
        $data = is_array($data) ? $data : [];
        unset($data[$key]);
        self::set($pointer, $data);
    }

    /**
     * Reset data in config with config file
     *
     * @param string $key
     */
    public static function reset($key)
    {
        self::push($key, null, 'reset');
    }

    /**
     * Add data in config
     *
     * @param string $key
     * @param string $value
     */
    public static function add($key, $value)
    {
        self::push($key, $value, 'add');
    }

    /**
     * Save data on config file
     *
     * @param string $name
     */
    public static function save($name)
    {
        if (empty($name) || HelperString::has($name, '.')) return;
        $data = self::get($name);
        $filename = $name . '.config.php';

        if (!HelperString::firstHas($filename, '~')) {
            $file = Dir::path('pinker/config/' . $filename, self::$app);
        } else {
            $filename = HelperString::firstDelete($filename, '~');
            $file = Dir::path('~pincore/pinker/config/' . $filename);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox config file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . var_export($data, true) . ";\n\n//end of config";

        File::generate($file, $data_for_save);
    }
}
    
