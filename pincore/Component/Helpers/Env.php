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


namespace Pinoox\Component\Helpers;


use Pinoox\Component\Store\Config\Data\DataManager;
use Pinoox\Portal\FileSystem;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Templating\Storage\FileStorage;

class Env
{
    private static $dataManager;
    private static $initData;

    public function __construct(private readonly string $basePath = '')
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

    public function restore(): void
    {
        self::$dataManager->setData(self::$initData);
    }

    public function register(): void
    {
        $dotenv = new Dotenv();
        $path = $this->basePath . '/.env';
        try {
            $dotenv->bootEnv($path);

            if (isset($_SERVER['SYMFONY_DOTENV_VARS']))
                unset($_SERVER['SYMFONY_DOTENV_VARS']);

            if (isset($_ENV['SYMFONY_DOTENV_VARS']))
                unset($_ENV['SYMFONY_DOTENV_VARS']);
        }catch (PathException $e)
        {

        }
    }
}