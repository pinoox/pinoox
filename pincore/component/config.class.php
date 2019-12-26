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
     * Set Target data in config
     *
     * @param string $pointer
     * @param string $key
     * @param mixed $value
     * @param array|string $ignore
     */
    public static function setLinear($pointer, $key, $value, $ignore = ['.'])
    {
        $key = !empty($ignore)? str_replace($ignore,'',$key) : $key;
        $key = $pointer .'.'. $key;
        self::push($key, $value, 'set');
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
     * Reset data in config with config file
     *
     * @param string $key
     */
    public static function reset($key)
    {
        self::push($key, null, 'reset');
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

        if(isset(self::$data[$app][$filename]) && $type == 'reset')
            unset(self::$data[$app][$filename]);

        if($type == 'reset') return;

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
     * Load config file
     *
     * @param $app
     * @param $filename
     * @return mixed|null
     */
    private static function loadFile($app, $filename)
    {
        if ($app !== '~')
            $file = Dir::path('config/' . $filename . '.config.php', $app);
        else
            $file = Dir::path('~pincore/config/' . $filename . '.config.php');

        if (is_file($file)) {
            return (include $file);
        }

        return null;
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
            $file = Dir::path('config/' . $filename, self::$app);
        } else {
            $filename = HelperString::firstDelete($filename, '~');
            $file = Dir::path('~pincore/config/' . $filename);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox config file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . var_export($data, true) . ";\n\n//end of config";

        File::generate($file, $data_for_save);
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
     * Get data from config
     *
     * @param string $pointer
     * @param string $key
     * @param array|string $ignore
     */
    public static function getLinear($pointer, $key, $ignore = ['.'])
    {
        $key = !empty($ignore)? str_replace($ignore,'',$key) : $key;
        $key = $pointer .'.'. $key;
        self::get($key);
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
}
    
