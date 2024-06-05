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

use ArrayIterator as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface as ObjectPortal4;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface as ObjectPortal3;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag as ObjectPortal2;

/**
 * @method static bool start()
 * @method static bool has(string $name)
 * @method static mixed get(string $name, mixed $default = NULL)
 * @method static set(string $name, mixed $value)
 * @method static array all()
 * @method static replace(array $attributes)
 * @method static mixed remove(string $name)
 * @method static clear()
 * @method static bool isStarted()
 * @method static ObjectPortal1 getIterator()
 * @method static int count()
 * @method static int getUsageIndex()
 * @method static bool isEmpty()
 * @method static bool invalidate(?int $lifetime = NULL)
 * @method static bool migrate(bool $destroy = false, ?int $lifetime = NULL)
 * @method static save()
 * @method static string getId()
 * @method static setId(string $id)
 * @method static string getName()
 * @method static setName(string $name)
 * @method static ObjectPortal2 getMetadataBag()
 * @method static registerBag(\Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag)
 * @method static ObjectPortal3 getBag(string $name)
 * @method static ObjectPortal4 getFlashBag()
 * @method static \Pinoox\Component\Store\Session ___()
 *
 * @see \Pinoox\Component\Store\Session
 */
class Session extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Pinoox\Component\Store\Session::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'session';
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
