<?php

namespace Pinoox\Portal;

use Pinoox\Component\Redis\Cache\RedisCacheStore;
use Pinoox\Component\Source\Portal;
use Pinoox\Support\SystemConfig;
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
 * @method static \Symfony\Component\Cache\Psr16Cache|RedisCacheStore ___()
 *
 * @see \Symfony\Component\Cache\Psr16Cache
 */
class Cache extends Portal
{
	public static function __register(): void
	{
		$config = Config::name('~cache')->get();
        $store = $config['default'] ?? 'file';
        $stores = $config['stores'] ?? [];
        $storeConfig = $stores[$store] ?? $stores['file'] ?? [];

        if (($storeConfig['driver'] ?? $store) === 'redis') {
            $connection = (string) ($storeConfig['connection'] ?? 'cache');
            self::__bind(RedisCacheStore::class)->setFactory(static function () use ($connection) {
                return new RedisCacheStore(Redis::connection($connection));
            });
            static::__container()->setAlias(CacheInterface::class, self::__id());

            return;
        }

		$directory = SystemConfig::resolvePath($storeConfig['path'] ?? '~storage/cache');
		$namespace = $storeConfig['namespace'] ?? $config['prefix'] ?? 'pinoox';
		$lifetime = (int) ($storeConfig['ttl'] ?? 0);
		
		self::__param('cache_directory', $directory);
		self::__param('cache_namespace', $namespace);
		self::__param('cache_lifetime', $lifetime);

		self::__bind(FilesystemAdapter::class, 'pool')->setArguments([
		    $namespace,
		    $lifetime,
		    $directory,
		]);

		self::__bind(Psr16Cache::class)->setArguments([
		    self::__ref('pool'),
		]);

		static::__container()->setAlias(CacheItemPoolInterface::class, self::__id('pool'));
		static::__container()->setAlias(CacheInterface::class, self::__id());
	}

	public static function __name(): string
	{
		return 'cache';
	}

	public static function __callback(): array
	{
		return [
			'reset'
		];
	}
}

