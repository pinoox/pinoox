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
    public function __construct($packageName = null, $isApp = false)
    {
        $this->isApp = $isApp;
        $this->build(self::$app, $packageName);
    }

    /**
     * build app information
     *
     * @param string|null $app
     * @param string|null $path
     */
    private function build($app = null, $packageName = null)
    {
        if (!is_file($packageName)) {
            $app = ($app === '~') ? null : $app;
            $this->path = (empty($packageName)) ? Dir::path('pinker/app.php', $app) : Dir::path('pinker/app.php', $packageName);
            if (!is_file($this->path)) {
                $app_file = (empty($packageName)) ? Dir::path('app.php', $app) : Dir::path('app.php', $packageName);
                if (is_file($app_file))
                    File::copy($app_file, $this->path);
            }
        } else {
            $this->path = $packageName;
        }


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
            $option = HelperString::camelCase($option);
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
     * @param string|null $packageName
     * @return AppProvider
     */
    public static function bake($packageName = null)
    {
        if (empty(self::$obj))
            self::$obj = new AppProvider($packageName, true);

        return self::$obj;
    }

    /**
     * Set data in app config
     *
     * @param string $key
     * @param string|Closure|boolean|array $value
     */
    public static function set($key, $value)
    {
        $key = HelperString::camelCase($key);
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
        $key = HelperString::camelCase($key);

        $result = null;
        if (empty($key))
            $result = self::$obj->data[self::$app];
        else
            $result = isset(self::$obj->data[self::$app][$key]) ? self::$obj->data[self::$app][$key] : $result;

        if (is_null($result)) {
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
        $app = (self::$app !== '~') ? self::$app : null;
        $file = Dir::path('pinker/app.php', $app);

        $data = self::get();
        $replaces = [];
        $printData = [];
        foreach ($data as $k => $v) {
            $k = HelperString::camelToUnderscore($k);
            if (is_callable($v)) {
                $replaces['{_{' . $k . '}_}'] = HelperObject::closure_dump($v);
                $printData[$k] = '{_{' . $k . '}_}';
            } else {
                $printData[$k] = $v;
            }
        }
        $printData = var_export($printData, true);

        foreach ($replaces as $k => $v) {
            $printData = str_replace("'$k'", $v, $printData);
        }

        $data_for_save = '<?' . 'php' . "\n";
        $data_for_save .= '//pinoox app file, generated at "' . gmdate('Y-m-d H:i') . "\"\n\n";
        $data_for_save .= 'return ' . $printData . ";\n\n//end of app";

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
        $key = HelperString::camelCase($key);
        if (empty($key))
            $result = $this->data;
        else
            $result = isset($this->data[$key]) ? $this->data[$key] : $result;

        if (is_null($result)) {

            if (isset(self::$$key))
                $result = self::$$key;
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
        $name = HelperString::camelCase($name);
        $this->data[$name] = $value;
    }
}