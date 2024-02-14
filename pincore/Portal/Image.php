<?php

/**
 * ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface as ObjectPortal1;
use Pinoox\Component\Source\Portal;

/**
 * @method static ImageManager withDriver(\Intervention\Image\Interfaces\DriverInterface|string $driver)
 * @method static ImageManager gd()
 * @method static ImageManager imagick()
 * @method static ObjectPortal1 create(int $width, int $height)
 * @method static ObjectPortal1 read(mixed $input, \Intervention\Image\Interfaces\DecoderInterface|array|string $decoders = [])
 * @method static ObjectPortal1 animate(callable $init)
 * @method static \Intervention\Image\ImageManager ___()
 *
 * @see \Intervention\Image\ImageManager
 */
class Image extends Portal
{
	public static function __register(): void
	{
		self::__bind(ImageManager::class)->setArguments([new Driver()]);
	}


	public static function __name(): string
	{
		return 'image';
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
