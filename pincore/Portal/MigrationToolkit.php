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

use Pinoox\Component\Migration\MigrationToolkit as ObjectPortal1;
use Pinoox\Component\Source\Portal;

/**
 * @method static ObjectPortal1 package($val)
 * @method static ObjectPortal1 action($action)
 * @method static ObjectPortal1 load()
 * @method static array getMigrations()
 * @method static string getMigrationPath()
 * @method static MigrationToolkit generateMigrationFileName($modelName)
 * @method static string getTableName()
 * @method static string filePath()
 * @method static string getMigrationName()
 * @method static getErrors($end = true)
 * @method static bool isSuccess()
 * @method static \Pinoox\Component\Migration\MigrationToolkit ___()
 *
 * @see \Pinoox\Component\Migration\MigrationToolkit
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
		return [
			'generateMigrationFileName'
		];
	}
}
