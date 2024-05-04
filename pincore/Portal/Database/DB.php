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

namespace Pinoox\Portal\Database;

use Doctrine\DBAL\Connection as ObjectPortal7;
use Doctrine\DBAL\Schema\AbstractSchemaManager as ObjectPortal6;
use Doctrine\DBAL\Schema\Column as ObjectPortal5;
use Generator as ObjectPortal2;
use Illuminate\Contracts\Container\Container as ObjectPortal14;
use Illuminate\Contracts\Database\Query\Expression as ObjectPortal4;
use Illuminate\Database\Capsule\Manager as ObjectPortal13;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Grammar as ObjectPortal12;
use Illuminate\Database\Query\Builder as ObjectPortal3;
use Illuminate\Database\Query\Grammars\Grammar as ObjectPortal9;
use Illuminate\Database\Query\Processors\Processor as ObjectPortal11;
use Illuminate\Database\Schema\Builder as ObjectPortal1;
use Illuminate\Database\Schema\Grammars\Grammar as ObjectPortal10;
use Illuminate\Events\Dispatcher;
use PDO as ObjectPortal8;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\Config;

/**
 * @method static useDefaultQueryGrammar()
 * @method static useDefaultSchemaGrammar()
 * @method static useDefaultPostProcessor()
 * @method static ObjectPortal1 getSchemaBuilder()
 * @method static ObjectPortal3 table($table, $as = NULL, $connection = NULL)
 * @method static ObjectPortal3 query()
 * @method static mixed selectOne($query, $bindings = [], $useReadPdo = true)
 * @method static mixed scalar($query, $bindings = [], $useReadPdo = true)
 * @method static array selectFromWriteConnection($query, $bindings = [])
 * @method static array select($query, $bindings = [], $useReadPdo = true)
 * @method static array selectResultSets($query, $bindings = [], $useReadPdo = true)
 * @method static ObjectPortal2 cursor($query, $bindings = [], $useReadPdo = true)
 * @method static bool insert($query, $bindings = [])
 * @method static int update($query, $bindings = [])
 * @method static int delete($query, $bindings = [])
 * @method static bool statement($query, $bindings = [])
 * @method static int affectingStatement($query, $bindings = [])
 * @method static bool unprepared($query)
 * @method static array pretend(\Closure $callback)
 * @method static mixed withoutPretending(\Closure $callback)
 * @method static bindValues($statement, $bindings)
 * @method static array prepareBindings(array $bindings)
 * @method static logQuery($query, $bindings, $time = NULL)
 * @method static whenQueryingForLongerThan($threshold, $handler)
 * @method static allowQueryDurationHandlersToRunAgain()
 * @method static float totalQueryDuration()
 * @method static resetTotalQueryDuration()
 * @method static mixed|false reconnect()
 * @method static reconnectIfMissingConnection()
 * @method static disconnect()
 * @method static Connection beforeStartingTransaction(\Closure $callback)
 * @method static Connection beforeExecuting(\Closure $callback)
 * @method static listen(\Closure $callback)
 * @method static ObjectPortal4 raw($value)
 * @method static string escape($value, $binary = false)
 * @method static bool hasModifiedRecords()
 * @method static recordsHaveBeenModified($value = true)
 * @method static Connection setRecordModificationState(bool $value)
 * @method static forgetRecordModificationState()
 * @method static Connection useWriteConnectionWhenReading($value = true)
 * @method static bool isDoctrineAvailable()
 * @method static bool usingNativeSchemaOperations()
 * @method static ObjectPortal5 getDoctrineColumn($table, $column)
 * @method static ObjectPortal6 getDoctrineSchemaManager()
 * @method static ObjectPortal7 getDoctrineConnection()
 * @method static DB registerDoctrineType(\Doctrine\DBAL\Types\Type|string $class, string $name, string $type)
 * @method static ObjectPortal8 getPdo()
 * @method static \PDO|\Closure|null getRawPdo()
 * @method static ObjectPortal8 getReadPdo()
 * @method static \PDO|\Closure|null getRawReadPdo()
 * @method static Connection setPdo($pdo)
 * @method static Connection setReadPdo($pdo)
 * @method static Connection setReconnector(callable $reconnector)
 * @method static string|null getName()
 * @method static string|null getNameWithReadWriteType()
 * @method static string getDriverName()
 * @method static ObjectPortal9 getQueryGrammar()
 * @method static Connection setQueryGrammar(\Illuminate\Database\Query\Grammars\Grammar $grammar)
 * @method static ObjectPortal10 getSchemaGrammar()
 * @method static Connection setSchemaGrammar(\Illuminate\Database\Schema\Grammars\Grammar $grammar)
 * @method static ObjectPortal11 getPostProcessor()
 * @method static Connection setPostProcessor(\Illuminate\Database\Query\Processors\Processor $processor)
 * @method static \Illuminate\Contracts\Events\Dispatcher|null getEventDispatcher()
 * @method static setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $dispatcher)
 * @method static unsetEventDispatcher()
 * @method static Connection setTransactionManager($manager)
 * @method static unsetTransactionManager()
 * @method static bool pretending()
 * @method static array getQueryLog()
 * @method static array getRawQueryLog()
 * @method static flushQueryLog()
 * @method static enableQueryLog()
 * @method static disableQueryLog()
 * @method static bool logging()
 * @method static string getDatabaseName()
 * @method static Connection setDatabaseName($database)
 * @method static Connection setReadWriteType($readWriteType)
 * @method static string getTablePrefix()
 * @method static Connection setTablePrefix($prefix)
 * @method static ObjectPortal12 withTablePrefix(\Illuminate\Database\Grammar $grammar)
 * @method static resolverFor($driver, \Closure $callback)
 * @method static mixed getResolver($driver)
 * @method static mixed transaction(\Closure $callback, $attempts = 1)
 * @method static beginTransaction()
 * @method static commit()
 * @method static rollBack($toLevel = NULL)
 * @method static int transactionLevel()
 * @method static afterCommit($callback)
 * @method static macro($name, $macro)
 * @method static mixin($mixin, $replace = true)
 * @method static bool hasMacro($name)
 * @method static flushMacros()
 * @method static DB setPrefix(string $prefix)
 * @method static \Illuminate\Contracts\Database\Query\Expression|string orderColumn(array|string $field)
 * @method static string orderDirection(string $type)
 * @method static Connection connection($connection = NULL)
 * @method static ObjectPortal1 schema($connection = NULL)
 * @method static Connection getConnection($name = NULL)
 * @method static addConnection(array $config, $name = 'default')
 * @method static bootEloquent()
 * @method static ObjectPortal13 setFetchMode($fetchMode)
 * @method static DatabaseManager getDatabaseManager()
 * @method static setAsGlobal()
 * @method static ObjectPortal14 getContainer()
 * @method static setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Pinoox\Component\Database\DatabaseManager ___()
 *
 * @see \Pinoox\Component\Database\DatabaseManager
 */
class DB extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Database\DatabaseManager::class)->setArguments([
		    Container::Illuminate()
		]);
	}


	/**
	 * @throws Exception
	 */
	public static function register(): void
	{
		$config = self::getConfig();
		// add default connection
		self::addConnection($config);

		// Set the event dispatcher used by Eloquent models... (optional)
		self::setEventDispatcher(new Dispatcher(Container::Illuminate()));

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
