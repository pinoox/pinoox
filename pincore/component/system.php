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

class System
{

    public static $currentLang = 'fa';
    public static $data = null;
    private static $type = null;
    private static $current = null;
    private static $obj = null;
    private static $isLoadAll;
    private static $noLoad = null;
    private static $classCache = null;

    private static function create($type, $name)
    {
        if (empty(self::$obj))
            self::$obj = new System();

        self::$type = $type;
        if (!isset($isLoadAll[$type])) {
            $isLoadAll[$type] = false;
        }
        if (!isset(self::$data[$type])) {
            self::$data[$type] = null;
        }

        self::run($name);
    }

    private static function run($name)
    {
        $load = 'load' . ucfirst(self::$type);
        $loadAll = 'loadAll' . ucfirst(self::$type);
        self::$current = $name;
        if (!empty($name)) {
            if (isset(self::$noLoad[self::$type][$name]))
                return self::$obj;
            self::$data[self::$type][$name] = self::$load($name);
        } else {
            if (self::$isLoadAll[self::$type])
                return self::$obj;
            if (!empty(self::$data[self::$type])) {
                self::$data[self::$type] = array_merge(self::$data[self::$type], self::$loadAll());
            } else {
                self::$data[self::$type] = self::$loadAll();
            }
        }
    }

    public static function cache($name = null)
    {
        self::service('cache.' . $name);
        if (empty(self::$classCache))
            self::$classCache = new Cache();
        self::$classCache->setPath(CACHE_PATH);

        self::create('cache', $name);
        return self::$obj;
    }

    private static function loadCache($name)
    {
        self::$noLoad[self::$type][$name] = true;
        return self::$classCache->get($name);
    }

    private static function loadAllCache()
    {
        self::$isLoadAll[self::$type] = true;
        return self::$classCache->getAll();
    }

    public function get()
    {
        $result = (!empty(self::$current)) ? self::$data[self::$type][self::$current] : self::$data[self::$type];
        if (func_num_args() >= 1) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if (isset($result[$arg]))
                    $result = $result[$arg];
                else
                    return null;
            }
        }
        return $result;
    }

    public function exists()
    {
        $result = (!empty(self::$current)) ? self::$data[self::$type][self::$current] : self::$data[self::$type];
        if (func_num_args() >= 1) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if (isset($result[$arg]))
                    $result = $result[$arg];
                else
                    return false;
            }
        }
        return (!empty($result)) ? true : false;
    }

    public function first()
    {
        $result = (!empty(self::$current)) ? self::$data[self::$type][self::$current] : self::$data[self::$type];
        $result = array_values($result)[0];
        return $result;
    }

    public function firstKey()
    {
        $result = (!empty(self::$current)) ? self::$data[self::$type][self::$current] : self::$data[self::$type];
        $result = (is_array($result)) ? array_keys($result)[0] : null;
        return $result;
    }

    public function set($value)
    {
        if (!empty(self::$current))
            self::$data[self::$type][self::$current] = $value;
        else
            self::$data[self::$type] = $value;

        return self::$obj;
    }

    public function add($value)
    {
        $key = null;
        if (func_num_args() >= 2) {
            $args = func_get_args();
            $key = $value;
            $value = $args[1];
        }

        if (!empty(self::$current)) {
            if (!empty($key)) {
                self::$data[self::$type][self::$current][$key] = $value;
            } else {
                if (isset(self::$data[self::$type]))
                    self::$data[self::$type][self::$current][] = $value;
            }
        } else {
            if (!empty($key)) {
                self::$data[self::$type][$key] = $value;
            } else {
                self::$data[self::$type][] = $value;
            }
            self::$data[self::$type][] = $value;
        }

        return self::$obj;
    }

    public function remove($index = null)
    {
        if (!empty($index)) {
            if (isset(self::$data[self::$type][self::$current]))
                self::$data[self::$type][self::$current] = null;
        } else {
            self::$data[self::$type] = null;
        }

        return self::$obj;
    }

    public function reset($name = null)
    {
        if (!empty($name)) {
            self::$data[self::$type] = null;
            self::$noLoad[self::$type] = null;
            self::$isLoadAll[self::$type] = false;
        } else {
            if (isset(self::$data[self::$type][$name]))
                unset(self::$data[self::$type][$name]);
            if (isset(self::$noLoad[self::$type][$name]))
                unset(self::$noLoad[self::$type][$name]);
        }

        return self::$obj;
    }

    public function save(/*method save just use in cache and config*/)
    {
        $method = 'save' . ucfirst(self::$type);
        if (method_exists(self::class, $method)) {
            if (empty(self::$current)) {
                $keys = array_keys(self::$data[self::$type]);
                foreach ($keys as $key) {
                    $this->$method($key, (self::$data[self::$type][$key]));
                }
            } else {
                $this->$method(self::$current, (self::$data[self::$type][self::$current]));
            }

        }

        return self::$obj;
    }

    private function saveCache($name, $data)
    {
        self::$classCache->save($name, $data);
    }

    public static function phpVersion()
    {
        return phpversion();
    }

    public static function mysqlVersion() {
        $output = shell_exec('mysql -V');
        if(empty($output)) return null;
        preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
        return (isset($version[0]))? $version[0] : null;
    }

    public static function hasModuleApache($module_name)
    {
        return in_array($module_name,apache_get_modules());
    }

    public static function freeSpace($unit = 'GB',$round = 1)
    {
        $freeSpace = disk_free_space(path('~'));
        return File::convert_size($freeSpace,'B',$unit,$round);
    }

    public static function totalSpace($unit = 'GB',$round = 1)
    {
        $totalSpace = disk_total_space(path('~'));
        return File::convert_size($totalSpace,'B',$unit,$round);
    }

    public static function useSpace($unit = 'GB',$round = 1)
    {
        return self::totalSpace($unit,$round) - self::freeSpace($unit,$round);
    }
}