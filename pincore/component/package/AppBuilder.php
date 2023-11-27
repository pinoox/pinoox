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

namespace pinoox\component\package;

use Exception;
use pinoox\component\Helpers\HelperArray;
use pinoox\portal\Config;
use pinoox\portal\Pinker;

class AppBuilder
{
    /**
     * Instance class
     *
     * @var AppBuilder|null
     */
    private static ?AppBuilder $obj = null;

    /**
     * Data app
     *
     * @var array
     */
    private static array $data = [];


    /**
     * package name app
     *
     * @var string
     */
    private string $packageName;

    /**
     * AppBuilder constructor
     *
     * @param string $packageName
     * @param bool $isFile
     * @throws Exception
     */
    public function __construct(string $packageName, bool $isFile = false)
    {
        $this->packageName = $packageName;

        if (!$isFile)
            $this->initData();
    }

    /**
     * Init data
     * @throws Exception
     */
    private function initData()
    {
        if (!App::exists($this->packageName))
            throw new Exception('package `' . $this->packageName . '` not found!');

        if (!isset(self::$data[$this->packageName])) {
            $data = $this->getConfig();

            if ($data === false) {
                $this->createApp();
            } else {
                self::$data[$this->packageName] = $data;
            }
        }
    }

    /**
     * AppBuilder init
     *
     * @param string $packageName
     * @return AppBuilder
     * @throws Exception
     */
    public static function init(string $packageName): AppBuilder
    {
        self::$obj = new AppBuilder($packageName);

        return self::$obj;
    }

    /**
     * Get instance class
     *
     * @return AppBuilder|null
     */
    public static function getInstance(): ?AppBuilder
    {
        return self::$obj;
    }

    /**
     * Get all app config
     */
    private function getConfig()
    {
        $app = App::meeting($this->packageName, function () {
            return Pinker::file('app.php')->pickup();
        });

        if (empty($app) || !is_array($app))
            return false;

        $app['package'] = $this->packageName;
        return $app;
    }

    private function createApp()
    {
        $source = Config::name('~app>source')->get();
        $source['package'] = $this->packageName;
        self::$data[$this->packageName] = $source;
    }

    /**
     * Add data in config
     *
     * @param string $key
     * @param mixed $value
     * @return AppBuilder|null
     */
    public function add(string $key, $value): ?AppBuilder
    {
        HelperArray::pushingData($key, $value, 'add', self::$data[$this->packageName]);

        return self::$obj;
    }

    /**
     * Set data in config
     *
     * @param string $key
     * @param mixed $value
     * @return AppBuilder|null
     */
    public function set(string $key, $value): ?AppBuilder
    {
        HelperArray::pushingData($key, $value, 'set', self::$data[$this->packageName]);

        return self::$obj;
    }

    /**
     * Get data from config
     *
     * @param string|null $value
     * @return mixed|null
     */
    public function get(?string $value = null)
    {
        $app = self::$data[$this->packageName];
        $source = Config::name('~app>source')->get();
        $data = array_merge($source, $app);
        if (is_null($value)) return $data;
        $parts = explode('.', $value);
        return self::result($data, $parts);
    }

    /**
     * App builder create by file
     *
     * @param string $file
     * @return AppBuilder
     * @throws Exception
     */
    public static function file(string $file): AppBuilder
    {
        self::$data['file:' . $file] = Pinker::file($file)->pickup();
        self::$obj = new AppBuilder('file:' . $file,true);
        return self::$obj;
    }

    /**
     * Result request get data
     *
     * @param mixed $data
     * @param array|null $parts
     * @return mixed|null
     */
    private static function result($data, ?array $parts)
    {
        if (is_array($data)) {
            foreach ($parts as $value) {
                if (isset($data[$value])) {
                    $data = $data[$value];
                } else {
                    $data = null;
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Save data on config file
     */
    public function save(): ?AppBuilder
    {
        $data = self::$data[$this->packageName];
        App::meeting($this->packageName, function () use ($data) {
            Pinker::file('app.php')->data($data)->bake();
        });

        return self::$obj;
    }
}