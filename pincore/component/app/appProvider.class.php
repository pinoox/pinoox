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

use Closure;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperObject;
use pinoox\component\HelperString;

class AppProvider extends AppSource
{
    /**
     * An instance of AppProvider Class
     *
     * @var AppProvider
     */
    private static $obj = null;

    /**
     * Package name of app
     *
     * @var string
     */
    private static $app = '~';

    /**
     * App information in file app.php
     *
     * @var array
     */
    private $options = [];

    /**
     * Path current app config (app.php)
     *
     * @var null|string
     */
    private $path = null;

    /**
     * Given data array for apps
     *
     * @var array
     */
    private $data = [];

    /**
     * Check is store data by app
     *
     * @var bool
     */
    private $isApp = false;

    /**
     * AppProvider constructor
     *
     * @param string|null $path
     * @param bool $isApp
     */
    public function __construct($path = null, $isApp = false)
    {
        $this->isApp = $isApp;
        self::build(self::$app, $path);
    }

    /**
     * build app information
     *
     * @param string|null $app
     * @param string|null $path
     */
    private function build($app = null, $path = null)
    {
        $app = ($app === '~') ? null : $app;
        $this->path = (empty($path)) ? Dir::path('app.php', $app) : $path;
        $this->options = $this->getOptionsApp();
        $this->setOptionsApp();
    }

    /**
     * get array app config in file app.php
     *
     * @return array
     */
    private function getOptionsApp()
    {
        if (is_file($this->path))
            return (array)(include $this->path);
        else
            return [];
    }

    /**
     * set app config in data array
     */
    private function setOptionsApp()
    {
        foreach ($this->options as $option => $value) {
            if ($this->isApp)
                $this->data[self::$app][$option] = $value;
            else
                $this->data[$option] = $value;

        }
    }

    /**
     *  Change current app with package name
     *
     * @param string|null $packageName for example "com_pinoox_manager"
     * @return AppProvider
     */
    public static function app($packageName = null)
    {
        if (is_null($packageName)) {
            return self::$obj;
        } else {
            self::$app = $packageName;

            if (!isset(self::$obj->data[self::$app]))
                self::$obj->build(self::$app);

        }

        return self::$obj;
    }

    /**
     * Create an instance AppProvider
     *
     * @param string|null $path
     * @return AppProvider
     */
    public static function bake($path = null)
    {
        if (empty(self::$obj))
            self::$obj = new AppProvider($path, true);

        return self::$obj;
    }

    /**
     * Set data in app config
     *
     * @param string $key
     * @param string|Closure $value
     */
    public static function set($key, $value)
    {
        $key = HelperString::camelToUnderscore($key);
        self::$obj->data[self::$app][$key] = $value;
    }

    /**
     * Call closure data in app config
     *
     * @param string $key
     * @return mixed|null
     */
    public static function call($key)
    {
        $func = self::get($key);

        if (!empty($func) && is_callable($func))
            return call_user_func($func);

        return null;
    }

    /**
     * Get data in app config
     *
     * @param string|null $key
     * @return string|array|mixed|null
     */
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

    /**
     * Save data on file app.php
     */
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

        File::generate($file, $data_for_save);
    }

    /**
     * Call features app
     *
     * Example) AppProvider::com_pinoox_manager('MainController')
     *
     * @param string $app
     * @param $arguments
     * @return mixed|bool|null
     */
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

    /**
     * Instance class into an app
     *
     * @param string $app example 'com_pinoox_manager'
     * @param string $path
     * @param string $class
     * @param string $type
     * @return mixed|null
     */
    private static function callClass($app, $path, $class, $type)
    {
        $path = !empty($path) ? $path . '\\' : '';
        $className = 'pinoox\\app\\' . $app . '\\' . $type . '\\' . $path . $class;
        if (class_exists($className)) {
            return new $className;
        }

        return null;
    }

    /**
     * Get data in app config
     *
     * @param string $key
     * @return array|mixed|null
     */
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

    /**
     *  Set data in app config
     *
     * @param string $name
     * @param string|Closure $value
     */
    public function __set($name, $value)
    {
        $name = HelperString::camelToUnderscore($name);
        $this->data[$name] = $value;
    }
}