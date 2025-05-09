<?php

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * @method static mixed get($key, $default = NULL)
 * @method static bool set($key, $value, $ttl = NULL)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = NULL)
 * @method static bool setMultiple($values, $ttl = NULL)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 * @method static bool prune()
 * @method static Cache reset()
 * @method static \Symfony\Component\Cache\Adapter\FilesystemAdapter ___pool()
 * @method static \Symfony\Component\Cache\Psr16Cache ___()
 *
 * @see \Symfony\Component\Cache\Psr16Cache
 */
class Cache extends Portal
{
	public static function __register(): void
	{
		// Load core configuration
		$config = Config::file('pinoox')->get('cache');

		// Set cache configuration values
		$directory = $config['directory'] ?? sys_get_temp_dir();
		$namespace = $config['default_namespace'] ?? '';
		$lifetime = $config['default_lifetime'] ?? 0;
		
		// Set container parameters
		self::__param('cache_directory', $directory);
		self::__param('cache_namespace', $namespace);
		self::__param('cache_lifetime', $lifetime);

		// Register PSR-6 cache pool
		self::__bind(FilesystemAdapter::class, 'pool')->setArguments([
		    $namespace,
		    $lifetime,
		    $directory,
		]);

		// Register PSR-16 cache
		self::__bind(Psr16Cache::class)->setArguments([
		    self::__ref('pool'),
		]);

		// Alias interfaces
		static::__container()->setAlias(CacheItemPoolInterface::class, self::__id('pool'));
		static::__container()->setAlias(CacheInterface::class, self::__id());
	}


	public static function __name(): string
	{
		return 'cache';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
			'reset'
		];
	}
}
