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

use Pinoox\Component\Source\Portal;

/**
 * @method static StubGenerator generate(string $stubFileName, string $outputPath, array $data = [])
 * @method static string get(string $stubFileName, array $data = [])
 * @method static \Pinoox\Component\StubGenerator object()
 *
 * @see \Pinoox\Component\StubGenerator
 */
class StubGenerator extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\StubGenerator::class)->setArguments([self::getStubsPath()]);
	}


	private static function getStubsPath(): string
	{
		return PINOOX_CORE_PATH . 'stubs/';
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'stub.generator';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
		    'generate'
		];
	}
}
