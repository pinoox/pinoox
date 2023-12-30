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

use Pinoox\Component\Migration\MigrationConfig as ObjectPortal1;
use Pinoox\Component\Source\Portal;

/**
 * @method static ObjectPortal1 load(string $path, string $package)
 * @method static bool isPrepareDB()
 * @method static getLastError()
 * @method static ?array getErrors()
 * @method static \Pinoox\Component\Migration\MigrationConfig ___()
 *
 * @see \Pinoox\Component\Migration\MigrationConfig
 */
class MigrationConfig extends Portal
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
		return 'migration.config';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
			'init',
		];
	}
}
