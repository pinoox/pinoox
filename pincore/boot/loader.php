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

namespace pinoox\boot;

define('PINOOX_CORE_PATH', PINOOX_PATH . 'pincore' . DIRECTORY_SEPARATOR);
define('PINOOX_BOOT_PATH', PINOOX_CORE_PATH . 'boot' . DIRECTORY_SEPARATOR);
define('PINOOX_MODEL_PATH', PINOOX_CORE_PATH . 'model' . DIRECTORY_SEPARATOR);
define('PINOOX_COMPONENT_PATH', PINOOX_CORE_PATH . 'component' . DIRECTORY_SEPARATOR);
define('PINOOX_SERVICE_PATH', PINOOX_CORE_PATH . 'service' . DIRECTORY_SEPARATOR);
define('PINOOX_CONFIG_PATH', PINOOX_CORE_PATH . 'config' . DIRECTORY_SEPARATOR);
define('PINOOX_LANG_PATH', PINOOX_CORE_PATH . 'lang' . DIRECTORY_SEPARATOR);
define('PINOOX_PATH_THUMB', 'thumbs/{name}_{size}.{ext}');

use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\Router;
use pinoox\component\Service;

class Loader
{
    private static $className;
    private static $fullClassName;
    private static $arrayCurrentClass = array();
    private static $listAllClass = array();

    public static function boot()
    {
        self::loadFunc();
        spl_autoload_register(__CLASS__ . '::load');
        self::loadServices();
    }

    private static function loadFunc()
    {
        include PINOOX_BOOT_PATH . 'functions.php';
    }

    private static function loadServices()
    {
        $services = Config::get('~service');
        foreach ($services as $service) {
            Service::run($service);
        }
    }

    public static function loadPath($key, $path)
    {
        $path = Dir::path($path);
        if (!is_file($path))
            return false;

        $app = Router::getApp();
        $key = $key.'['.$app.']';
        if (!in_array($key, self::$listAllClass)) {
            self::$listAllClass[] = $key;
            include_once $path;
            return true;
        }
        return false;
    }

    public static function getListClasses()
    {
        return self::$listAllClass;
    }

    private static function load($class)
    {
        self::loadByConfig($class);
        if (in_array($class, self::$listAllClass)) return;
        self::$listAllClass[] = $class;
        self::$fullClassName = $class;
        $arrayName = explode('\\', $class);
        $countArrayName = count($arrayName);
        if ($countArrayName < 3 || $arrayName[0] != 'pinoox') return;

        self::$className = end($arrayName);
        self::$arrayCurrentClass = array_slice($arrayName, 2, $countArrayName - 3);

        switch ($arrayName[1]) {
            case 'model':
                self::checkModelLoader();
                break;
            case 'component':
                self::componentLoader();
                break;
            case 'service':
                self::serviceLoader();
                break;
            case 'app':
                self::appLoader();
                break;
        }
    }

    private static function loadByConfig($class)
    {
        if (self::checkNameSpace($class))
            return;

        $file = Config::get('~loader.' . $class);
        if (empty($file)) return;
        if (!is_array($file))
            $file = [$file];
        foreach ($file as $f) {
            $f = Dir::path($f);
            if (is_file($f)) {
                if (!in_array($class, self::$listAllClass))
                    self::$listAllClass[] = $class;
                include_once $f;
            }
        }

    }

    private static function checkNameSpace($classname)
    {
        $search = 'pinoox\\';
        return (substr($classname, 0, strlen($search)) == $search);
    }

    private static function checkModelLoader()
    {
        $className = self::$className;

        if (self::checkTypeClass($className, "Model")) {
            self::modelLoader();
        } else if (self::checkTypeClass($className, "Database")) {
            self::databaseLoader();
        }
    }

    private static function checkTypeClass($classname, $type)
    {
        if (substr($classname, -strlen($type)) == $type) {
            return true;
        }
        return false;
    }

    private static function modelLoader()
    {
        $className = lcfirst(str_replace('Model', '', self::$className));
        self::includeClass(PINOOX_MODEL_PATH, $className, 'model');
    }

    private static function includeClass($path, $name, $suffix = null)
    {
        $name = lcfirst($name);
        $suffix = empty($suffix) ? "" : "." . $suffix;
        $arrayClass = self::$arrayCurrentClass;
        $afterPath = implode(DIRECTORY_SEPARATOR, $arrayClass);
        $afterPath = empty($afterPath) ? "" : $afterPath . DIRECTORY_SEPARATOR;
        $file = $path . $afterPath . $name . $suffix . ".php";
        if (!file_exists($file)) {
            $name = strtolower($name);
            $file = $path . $afterPath . $name . $suffix . ".php";
            if (!file_exists($file)) return;
        }

        $fullClassName = self::$fullClassName;
        require_once $file;
        self::loadMagicsMethodsTrait($fullClassName);
    }

    private static function loadMagicsMethodsTrait($className)
    {
        if (!class_exists($className))
            return;

        $uses = class_uses($className);
        $uses = !empty($uses) ? $uses : [];
        if (in_array("pinoox\\component\\MagicTrait", $uses)) {
            if (method_exists($className, "__init")) {
                call_user_func(array($className, "__init"));
            }
        }
    }

    private static function databaseLoader()
    {
        $className = lcfirst(str_replace('Database', '', self::$className));
        self::includeClass(PINOOX_MODEL_PATH, $className, 'database');
    }

    private static function componentLoader($app = '~')
    {
        $className = self::$className;

        if (self::checkTypeClass($className, "Interface")) {
            self::interfaceLoader($app);
        } else if (self::checkTypeClass($className, "Trait")) {
            self::traitLoader($app);
        } else if (self::checkTypeClass($className, "Abstract")) {
            self::abstractLoader($app);
        } else {
            self::classLoader($app);
        }
    }

    private static function interfaceLoader($app = '~')
    {
        $path = ($app === '~') ? PINOOX_COMPONENT_PATH : Dir::path('component/', $app);
        $className = lcfirst(str_replace('Interface', '', self::$className));
        self::includeClass($path, $className, 'interface');
    }

    private static function traitLoader($app = '~')
    {
        $path = ($app === '~') ? PINOOX_COMPONENT_PATH : Dir::path('component/', $app);
        $className = lcfirst(str_replace('Trait', '', self::$className));
        self::includeClass($path, $className, 'trait');
    }

    private static function abstractLoader($app = '~')
    {
        $path = ($app === '~') ? PINOOX_COMPONENT_PATH : Dir::path('component/', $app);
        $className = lcfirst(str_replace('Abstract', '', self::$className));
        self::includeClass($path, $className, 'abstract');
    }

    private static function classLoader($app = '~')
    {
        $path = ($app === '~') ? PINOOX_COMPONENT_PATH : Dir::path('component/', $app);
        $className = self::$className;
        self::includeClass($path, $className, 'class');
    }

    private static function serviceLoader()
    {
        $className = lcfirst(str_replace('Service', '', self::$className));
        self::includeClass(PINOOX_SERVICE_PATH, $className, 'service');
    }

    private static function appLoader()
    {
        $arrayClass = self::$arrayCurrentClass;

        if (!empty($arrayClass) && count($arrayClass) >= 2) {
            array_splice(self::$arrayCurrentClass, 0, 2);
            $app = $arrayClass[0];
            switch ($arrayClass[1]) {
                case 'model':
                    self::checkAppModelLoader($app);
                    break;
                case 'controller':
                    self::appController($app);
                    break;
                case 'service':
                    self::appService($app);
                    break;
                case 'component':
                    self::componentLoader($app);
                    break;
            }
        } else {
            exit('The application was not loaded correctly');
        }
    }

    private static function checkAppModelLoader($app)
    {
        $className = self::$className;

        if (self::checkTypeClass($className, "Model")) {
            self::appModelLoader($app);
        } else if (self::checkTypeClass($className, "Database")) {
            self::appDatabaseLoader($app);
        }
    }

    private static function appModelLoader($app)
    {
        $className = lcfirst(str_replace('Model', '', self::$className));
        self::includeClass(Dir::path('model/', $app), $className, 'model');
    }

    private static function appDatabaseLoader($app)
    {
        $className = lcfirst(str_replace('Database', '', self::$className));
        self::includeClass(Dir::path('model/', $app), $className, 'database');
    }

    private static function appController($app)
    {
        if (self::checkTypeClass(self::$className, 'Controller')) {
            self::appControllerLoader($app);
        } else if (self::checkTypeClass(self::$className, 'Configuration')) {
            self::appConfigurationLoader($app);
        }
    }

    private static function appControllerLoader($app)
    {
        $className = lcfirst(str_replace('Controller', '', self::$className));
        self::includeClass(Dir::path('controller/', $app), $className, 'controller');
    }

    private static function appConfigurationLoader($app)
    {
        $className = lcfirst(str_replace('Configuration', '', self::$className));
        self::includeClass(Dir::path('controller/', $app), $className, 'configuration');
    }

    private static function appService($app)
    {
        $path = ($app === '~') ? PINOOX_COMPONENT_PATH : Dir::path('service/', $app);
        $className = lcfirst(str_replace('Service', '', self::$className));
        self::includeClass($path, $className, 'service');
    }
}