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

namespace pinoox\component\app;

use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperObject;
use pinoox\component\HelperString;

class AppProvider extends AppSource
{
    private static $obj = null;
    private static $app = '~';
    private $options = [];
    private $path = null;
    private $data = [];
    private $isApp = false;

    public function __construct($path = null, $isApp = false)
    {
        $this->isApp = $isApp;
        self::build(self::$app, $path);
    }

    private function build($app = null, $path = null)
    {
        $app = ($app === '~') ? null : $app;
        $this->path = (empty($path)) ? Dir::path('app.php', $app) : $path;
        $this->options = $this->getOptionsApp();
        $this->setOptionsApp();
    }

    private function getOptionsApp()
    {
        if (is_file($this->path))
            return (array)(include $this->path);
        else
            return [];
    }

    private function setOptionsApp()
    {
        foreach ($this->options as $option => $value) {
            if ($this->isApp)
                $this->data[self::$app][$option] = $value;
            else
                $this->data[$option] = $value;

        }
    }

    public static function app($packageName = null)
    {
        if (is_null($packageName)) {
            return self::$app;
        } else {
            self::$app = $packageName;

            if (!isset(self::$obj->data[self::$app]))
                self::$obj->build(self::$app);

        }

        return self::$obj;
    }

    public static function bake($path = null)
    {
        if (empty(self::$obj))
            self::$obj = new AppProvider($path, true);

        return self::$obj;
    }

    public static function set($key, $value)
    {
        $key = HelperString::camelToUnderscore($key);
        self::$obj->data[self::$app][$key] = $value;
    }

    public static function call($key)
    {
        $func = self::get($key);

        if (!empty($func) && is_callable($func))
            call_user_func($func);
    }

    public static function get($key = null)
    {
        $result = null;
        if (empty($key))
            $result = self::$obj->data[self::$app];
        else
            $result = isset(self::$obj->data[self::$app][$key]) ? self::$obj->data[self::$app][$key] : $result;

        if (is_null($result)) {
            $key = HelperString::camelCase($key);

            if (isset(self::$$key))
                $result = self::$$key;
        }

        return $result;
    }

    public static function save()
    {
        $app = (self::$app !== '~')? self::$app : null;
        $file = Dir::path('app.php', $app);

        $data = self::get();
        $replaces = [];
        foreach ($data as $k => $v) {
            if (is_callable($v)) {
                $replaces['{_{' . $k . '}_}'] = HelperObject::closure_dump($v);
                $data[$k] = '{_{' . $k . '}_}';
            }
        }
        $data = var_export($data, true);

        foreach ($replaces as $k => $v) {
            $data = str_replace("'$k'", $v, $data);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox app file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . $data . ";\n\n//end of app";

        File::generate_file($file, $data_for_save);
    }

    public static function __callStatic($app, $arguments)
    {
        if (count($arguments) != 1) return false;
        $class = $arguments[0];
        $listClass = HelperString::multiExplode(['/', '\\', '>'], $class);
        $class = array_pop($listClass);
        $path = implode('\\', $listClass);
        $types = ['Controller', 'Model', 'Database'];
        foreach ($types as $type) {
            if (HelperString::lastHas($class, $type)) {
                $type = lcfirst($type);
                return self::callClass($app, $path, $class, $type);
            }
        }
        $type = 'component';
        return self::callClass($app, $path, $class, $type);
    }

    private static function callClass($app, $path, $class, $type)
    {
        $path = !empty($path) ? $path . '\\' : '';
        $className = 'pinoox\\app\\' . $app . '\\' . $type . '\\' . $path . $class;
        if (class_exists($className)) {
            return new $className;
        }

        return null;
    }

    public function getData()
    {

    }

    public function __get($key)
    {
        $result = null;
        if (empty($key))
            $result = $this->data;
        else
            $result = isset($this->data[$key]) ? $this->data[$key] : $result;

        if (is_null($result)) {
            $key = HelperString::camelCase($key);

            if (isset($this->$key))
                $result = $this->$key;
        }

        return $result;
    }

    public function __set($name, $value)
    {
        $name = HelperString::camelToUnderscore($name);
        $this->data[$name] = $value;
    }
}