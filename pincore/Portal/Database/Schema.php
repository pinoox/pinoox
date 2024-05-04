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

use Illuminate\Database\Connection as ObjectPortal1;
use Illuminate\Database\Schema\Builder as ObjectPortal2;
use Pinoox\Component\Source\Portal;

/**
 * @method static bool createDatabase($name)
 * @method static bool dropDatabaseIfExists($name)
 * @method static array getTables()
 * @method static array getViews()
 * @method static array getAllTables()
 * @method static array getAllViews()
 * @method static array getColumns($table)
 * @method static array getIndexes($table)
 * @method static array getForeignKeys($table)
 * @method static dropAllTables()
 * @method static dropAllViews()
 * @method static defaultStringLength($length)
 * @method static defaultMorphKeyType(string $type)
 * @method static morphUsingUuids()
 * @method static morphUsingUlids()
 * @method static useNativeSchemaOperationsIfPossible(bool $value = true)
 * @method static bool hasTable($table)
 * @method static bool hasView($view)
 * @method static array getTableListing()
 * @method static array getTypes()
 * @method static bool hasColumn($table, $column)
 * @method static bool hasColumns($table, array $columns)
 * @method static whenTableHasColumn(string $table, string $column, \Closure $callback)
 * @method static whenTableDoesntHaveColumn(string $table, string $column, \Closure $callback)
 * @method static string getColumnType($table, $column, $fullDefinition = false)
 * @method static array getColumnListing($table)
 * @method static array getIndexListing($table)
 * @method static bool hasIndex($table, $index, $type = NULL)
 * @method static table($table, \Closure $callback)
 * @method static create($table, \Closure $callback)
 * @method static drop($table)
 * @method static dropIfExists($table)
 * @method static dropColumns($table, $columns)
 * @method static dropAllTypes()
 * @method static rename($from, $to)
 * @method static bool enableForeignKeyConstraints()
 * @method static bool disableForeignKeyConstraints()
 * @method static mixed withoutForeignKeyConstraints(\Closure $callback)
 * @method static ObjectPortal1 getConnection()
 * @method static ObjectPortal2 setConnection(\Illuminate\Database\Connection $connection)
 * @method static blueprintResolver(\Closure $resolver)
 * @method static macro($name, $macro)
 * @method static mixin($mixin, $replace = true)
 * @method static bool hasMacro($name)
 * @method static flushMacros()
 * @method static \Illuminate\Database\Schema\MySqlBuilder ___()
 *
 * @see \Illuminate\Database\Schema\MySqlBuilder
 */
class Schema extends Portal
{
	public static function __register(): void
	{
		self::__bind(DB::schema());
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'schema';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}
}
