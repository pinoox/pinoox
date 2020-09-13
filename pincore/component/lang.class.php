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

class Lang
{
    private static $data = [];
    private static $tempData = [];
    private static $lang = PINOOX_DEFAULT_LANG;
    private static $app = null;

    public static function exists($lang,$app = null)
    {
        if(is_null($app))
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        if ($app !== '~')
            $path = Dir::path('lang/' . $lang . '/', $app);
        else
            $path = Dir::path('~pincore/lang/' . $lang . '/');

        return file_exists($path);
    }

    public static function current()
    {
        return self::$lang;
    }

    public static function change($lang)
    {
        self::$lang = $lang;
    }

    public static function app($packageName)
    {
        self::$app = $packageName;
    }

    public static function add($key, $value)
    {
        self::push($key, $value, 'add');
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

        if (!isset(self::$data[self::$lang][$app][$filename])) {
            self::$data[self::$lang][$app][$filename] = self::loadFile($app, $filename);
        }

        $temp = &self::$data[self::$lang][$app];

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
        }
    }

    private static function loadFile($app, $filename)
    {
        if ($app !== '~')
            $file = Dir::path('lang/' . self::$lang . '/' . $filename . '.lang.php', $app);
        else
            $file = Dir::path('~pincore/lang/' . self::$lang . '/' . $filename . '.lang.php');

        if (is_file($file)) {
            return (include $file);
        }

        return null;
    }

    public static function replace()
    {
        $text = '';
        $numargs = func_num_args();
        if ($numargs < 1) return $text;

        $args = func_get_args();
        $text = self::get($args[0]);

        //$text = !empty($text) ? $text : '{'.$args[0].'}';
        if (is_array($text)) return $text;
        $numargs--;
        if ($numargs < 1) return $text;
        array_shift($args);
        $replaces = $args[0];

        if (is_array($replaces)) {
            foreach ($replaces as $key => $replace) {
                $replace = is_array($replace) ? HelperString::encodeJson($replace) : $replace;
                $text = str_replace("{" . $key . "}", $replace, $text);
            }
            return $text;
        }
        for ($i = 0; $i < $numargs; $i++) {
            $replace = $args[$i];
            $replace = (is_array($replace)) ? HelperString::encodeJson($replace) : $replace;
            $text = str_replace("{" . $i . "}", $replace, $text);
        }

        return $text;
    }

    public static function get($value)
    {
        if (empty($value)) return null;
        $default = '{' . $value . '}';
        $info = explode('.', $value);
        $filename = array_shift($info);
        $filename = str_replace(['/', '\\'], '>', $filename);

        if (HelperString::firstHas($filename, '~')) {
            $filename = HelperString::firstDelete($filename, '~');
            $app = '~';
        } else {
            $app = (empty(self::$app)) ? Router::getApp() : self::$app;
        }

        if (!isset(self::$data[self::$lang][$app][$filename])) {
            self::$data[self::$lang][$app][$filename] = self::loadFile($app, $filename);
        }

        $result = self::result($app, $filename, $info);
        return (!empty($result)) ? $result : $default;
    }

    private static function result($app, $filename, $info, $isTemp = false)
    {
        $result = ($isTemp) ? self::$tempData[$app][$filename] : self::$data[self::$lang][$app][$filename];
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

    public static function save($name)
    {
        if (empty($name) || HelperString::has($name, '.')) return;
        $data = self::get($name);
        $filename = $name . '.lang.php';

        if (!HelperString::firstHas($filename, '~')) {
            $file = Dir::path('lang/' . self::$lang . '/' . $filename, self::$app);
        } else {
            $filename = HelperString::firstDelete($filename, '~');
            $file = Dir::path('~pincore/lang/' . self::$lang . '/' . $filename);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox lang file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . var_export($data, true) . ";\n\n//end of lang";

        File::generate($file, $data_for_save);
    }

    /**
     * Set target data in lang
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

    public static function set($key, $value)
    {
        self::push($key, $value, 'set');
    }

    /**
     * Get target data from lang
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

}