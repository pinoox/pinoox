<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Query\Builder as ObjectPortal3;
use Illuminate\Database\Schema\Builder as ObjectPortal1;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Source\Portal;
use Closure;
use Throwable;

/**
 * @method static DB setPrefix(string $prefix)
 * @method static Connection connection($connection = NULL)
 * @method static ObjectPortal3 table($table, $as = NULL, $connection = NULL)
 * @method static ObjectPortal1 schema($connection = NULL)
 * @method static Connection getConnection($name = NULL)
 * @method static DatabaseManager getDatabaseManager()
 * @method static addConnection(array $config, $name = 'default')
 * @method static bootEloquent()
 * @method static setFetchMode($fetchMode)
 * @method static getEventDispatcher()
 * @method static setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
 * @method static setAsGlobal()
 * @method static getContainer()
 * @method static setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Pinoox\Component\Database\DatabaseManager ___()
 *
 * @see \Pinoox\Component\Database\DatabaseManager
 */
class DB extends Portal
{
    public static function __register(): void
    {
        self::__bind(\Pinoox\Component\Database\DatabaseManager::class);
    }

    /**
     * @throws Exception
     */
    public static function boot(): void
    {
        $config = self::getConfig();
        // add default connection
        self::addConnection($config);

        // Set the event dispatcher used by Eloquent models... (optional)
        self::setEventDispatcher(new Dispatcher(new Container));

        //Make this Capsule instance available globally.
        self::setAsGlobal();
        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        self::bootEloquent();
    }

    /**
     * @throws Exception
     */
    public static function getConfig($key = null)
    {
        //get configs
        $mode = self::mode();
        if (!($config = Config::name('~database')->getLinear(null, $mode)))
            throw new Exception('Database config "' . $mode . '" not defined');

        return $config[$key] ?? $config;
    }

    public static function mode()
    {
        return Config::name('~pinoox')->get('mode');
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'database';
    }


    /**
     * @throws Throwable
     */
    public static function beginTransaction(): void
    {
        DB::connection()->beginTransaction();
    }

    /**
     * @throws Throwable
     */
    public static function commit(): void
    {
        DB::connection()->commit();
    }

    /**
     * @throws Throwable
     */
    public static function rollBack($toLevel = null): void
    {
        DB::connection()->rollBack($toLevel);
    }

    public static function hasConnection(): bool
    {
        try {
            self::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @throws Throwable
     */
    public static function transaction(Closure $callback, $attempts = 1): void
    {
        DB::connection()->transaction($callback, $attempts);
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'setPrefix'
        ];
    }
}
