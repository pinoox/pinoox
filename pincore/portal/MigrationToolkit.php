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

namespace pinoox\portal;

use pinoox\component\migration\MigrationToolkit as ObjectPortal1;
use pinoox\component\source\Portal;

/**
 * @method static ObjectPortal1 appPath($val)
 * @method static ObjectPortal1 package($val)
 * @method static ObjectPortal1 namespace($val)
 * @method static ObjectPortal1 action($action)
 * @method static ObjectPortal1 migrationPath($val)
 * @method static ObjectPortal1 load()
 * @method static array getMigrations()
 * @method static string generateMigrationFileName($modelName)
 * @method static getErrors($end = true)
 * @method static bool isSuccess()
 * @method static \pinoox\component\migration\MigrationToolkit ___()
 *
 * @see \pinoox\component\migration\MigrationToolkit
 */
class MigrationToolkit extends Portal
{
	public static function __register(): void
	{
		self::__bind(ObjectPortal1::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'migration.toolkit';
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
