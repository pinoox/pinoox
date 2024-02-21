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

use Illuminate\Contracts\Filesystem\Cloud as ObjectPortal6;
use Illuminate\Contracts\Filesystem\Filesystem as ObjectPortal5;
use Illuminate\Filesystem\FilesystemAdapter as ObjectPortal1;
use League\Flysystem\FilesystemAdapter as ObjectPortal4;
use League\Flysystem\FilesystemOperator as ObjectPortal3;
use Pinoox\Component\Source\Portal;
use Pinoox\component\Store\FileSystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse as ObjectPortal2;

/**
 * @method static bool exists($path)
 * @method static string|null get($path)
 * @method static readStream($path)
 * @method static string|bool put($path, $contents, $options = [])
 * @method static writeStream($path, $resource, array $options = [])
 * @method static string getVisibility($path)
 * @method static bool setVisibility($path, $visibility)
 * @method static bool prepend($path, $data, $separator = '')
 * @method static bool append($path, $data, $separator = '')
 * @method static bool delete($paths)
 * @method static bool copy($from, $to)
 * @method static bool move($from, $to)
 * @method static int size($path)
 * @method static int lastModified($path)
 * @method static array files($directory = NULL, $recursive = false)
 * @method static array allFiles($directory = NULL)
 * @method static array directories($directory = NULL, $recursive = false)
 * @method static array allDirectories($directory = NULL)
 * @method static bool makeDirectory($path)
 * @method static bool deleteDirectory($directory)
 * @method static ObjectPortal1 assertExists($path, $content = NULL)
 * @method static ObjectPortal1 assertMissing($path)
 * @method static ObjectPortal1 assertDirectoryEmpty($path)
 * @method static bool missing($path)
 * @method static bool fileExists($path)
 * @method static bool fileMissing($path)
 * @method static bool directoryExists($path)
 * @method static bool directoryMissing($path)
 * @method static string path($path)
 * @method static array|null json($path, $flags = 0)
 * @method static ObjectPortal2 response($path, $name = NULL, array $headers = [], $disposition = 'inline')
 * @method static ObjectPortal2 download($path, $name = NULL, array $headers = [])
 * @method static string|false putFile($path, $file = NULL, $options = [])
 * @method static string|false putFileAs($path, $file, $name = NULL, $options = [])
 * @method static string|false checksum(string $path, array $options = [])
 * @method static string|false mimeType($path)
 * @method static string url($path)
 * @method static bool providesTemporaryUrls()
 * @method static string temporaryUrl($path, $expiration, array $options = [])
 * @method static array temporaryUploadUrl($path, $expiration, array $options = [])
 * @method static ObjectPortal3 getDriver()
 * @method static ObjectPortal4 getAdapter()
 * @method static array getConfig()
 * @method static buildTemporaryUrlsUsing(\Closure $callback)
 * @method static \Illuminate\Filesystem\FilesystemAdapter|\TWhenReturnType when($value = NULL, ?callable $callback = NULL, ?callable $default = NULL)
 * @method static \Illuminate\Filesystem\FilesystemAdapter|\TUnlessReturnType unless($value = NULL, ?callable $callback = NULL, ?callable $default = NULL)
 * @method static macro($name, $macro)
 * @method static mixin($mixin, $replace = true)
 * @method static bool hasMacro($name)
 * @method static flushMacros()
 * @method static mixed macroCall($method, $parameters)
 * @method static ObjectPortal5 drive($name = NULL)
 * @method static ObjectPortal5 disk($name = NULL)
 * @method static ObjectPortal6 cloud()
 * @method static ObjectPortal5 build($config)
 * @method static ObjectPortal5 createLocalDriver(array $config)
 * @method static ObjectPortal5 createFtpDriver(array $config)
 * @method static ObjectPortal5 createSftpDriver(array $config)
 * @method static ObjectPortal6 createS3Driver(array $config)
 * @method static ObjectPortal5 createScopedDriver(array $config)
 * @method static FilesystemManager set($name, $disk)
 * @method static string getDefaultDriver()
 * @method static string getDefaultCloudDriver()
 * @method static FilesystemManager forgetDisk($disk)
 * @method static purge($name = NULL)
 * @method static FilesystemManager extend($driver, \Closure $callback)
 * @method static setConfig(\Pinoox\Component\Store\Config\ConfigInterface $config)
 * @method static \Pinoox\component\Store\FileSystem\FilesystemManager ___()
 *
 * @see \Pinoox\component\Store\FileSystem\FilesystemManager
 */
class Storage extends Portal
{
	public static function __register(): void
	{
		self::__bind(FilesystemManager::class)->setArguments([
		    Config::name('~filesystems')
		]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'storage';
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
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
