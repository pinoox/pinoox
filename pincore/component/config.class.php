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
    private static $data = [];
    private static $tempData = [];
    private static $app = null;

    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    public static function set($key, $value)
    {
        self::push($key, $value, 'set');
    }

    public static function setLinear($pointer,$key,$value,$ignore = ['.'])
    {
        $key = !empty($ignore)? str_replace($ignore,'',$key) : $key;
        $key = $pointer .'.'. $key;
        self::push($key, $value, 'set');
    }

    public static function remove($key)
    {
        self::push($key, null, 'del');
    }

    public static function reset($key)
    {
        self::push($key, null, 'reset');
    }

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

    public static function add($key, $value)
    {
        self::push($key, $value, 'add');
    }

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

        File::generate_file($file, $data_for_save);
    }

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
    
