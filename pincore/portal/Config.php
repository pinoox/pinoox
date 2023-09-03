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

use pinoox\portal\app\App;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\source\Portal;
use pinoox\component\store\Config as ObjectPortal1;

/**
 * @method static \pinoox\component\store\Config create(\pinoox\component\store\Pinker $pinker, mixed $merge = NULL)
 * @method static \pinoox\component\store\Config ___()
 *
 * @see \pinoox\component\store\Config
 */
class Config extends Portal
{
	const folder = 'config';

	public static function __register(): void
	{
		self::__bind(ObjectPortal1::class)->setArguments([Pinker::__ref()]);
	}


	/**
	 * Set file for pinoox baker
	 *
	 * @param string|ReferenceInterface $fileName
	 * @return ObjectPortal1
	 */
	public static function name(string|ReferenceInterface $fileName): ObjectPortal1
	{
		$fileName = $fileName . '.config.php';
		$reference = Path::prefixReference($fileName, self::folder);
		return Config::create(Pinker::file($reference));
	}


	/**
	 * Set file for pinoox baker
	 *
	 * @param string $name
	 */
	private function initData(string $name): void
	{
		$this->name = $name;
		$this->app = null;
		$parts = explode(':', $name);
		if (count($parts) === 2) {
		    $this->app = $parts[0];
		    $name = $parts[1];
		}

		$name = str_replace(['/', '\\'], '>', $name);
		$filename = $name;
		if (HelperString::firstHas($name, '~')) {
		    $filename = HelperString::firstDelete($filename, '~');
		    $appDefault = '~';
		} else {
		    $appDefault = App::package();
		}

		$this->app = !empty($app) ? $app : $appDefault;

		$file = 'config/' . $filename . '.config.php';
		$file = ($this->app === '~') ? '~' . $file : $file;
		$this->pinker->file($file);

		$this->key = $this->app . ':' . $filename;
		if (!isset(self::$data[$this->key])) {
		    $value = $this->pinker->pickup();
		    self::$data[$this->key] = $value;
		}
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'config';
	}


	/**
	 * Get include method names .
	 * @return string[]
	 */
	public static function __include(): array
	{
		return ['name','create'];
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
