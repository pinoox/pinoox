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
use Pinoox\Component\Upload\FileUploader as ObjectPortal2;
use Pinoox\Component\Upload\FileUploaderBuilder as ObjectPortal1;

/**
 * @method static ObjectPortal1 path(string $path)
 * @method static ObjectPortal1 inputKey(string $fileInputKey)
 * @method static ObjectPortal1 group(string $group)
 * @method static ObjectPortal1 setThumb(bool $isThumb = true)
 * @method static ObjectPortal1 extensions(array $allowedExtensions)
 * @method static ObjectPortal1 setFileIdAttribute(string $fileIdAttribute)
 * @method static ObjectPortal1 maxSize(string $sizeWithUnit)
 * @method static ObjectPortal1 model(\Pinoox\Component\Database\Model $model, string $attribute = 'file_id')
 * @method static ObjectPortal2 upload()
 * @method static ObjectPortal1 deleteOldFiles()
 * @method static \Pinoox\Component\Upload\FileUploaderBuilder ___()
 *
 * @see \Pinoox\Component\Upload\FileUploaderBuilder
 */
class Uploader extends Portal
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
		return 'uploader';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
			'deleteAssociatedFiles'
		];
	}
}
