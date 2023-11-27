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


namespace pinoox\component\Helpers;


use pinoox\component\store\config\data\DataManager;

class Env
{
    private static $dataManager;
    private static $initData;

    public function __construct()
    {
        self::$dataManager = new DataManager();
        self::$initData = $_ENV;
        self::$dataManager->setDataFollow($_ENV);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        return self::$dataManager->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        self::$dataManager->set($key, $value);
    }

    public function remove(string $key): void
    {
        self::$dataManager->remove($key);
    }

    public function restore()
    {
        self::$dataManager->setData(self::$initData);
       // $_ENV = self::$initData;
    }
}