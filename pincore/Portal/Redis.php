<?php

namespace Pinoox\Portal;

use Pinoox\Component\Redis\RedisConnection;
use Pinoox\Component\Redis\RedisManager;
use Pinoox\Component\Source\Portal;

/**
 * @method static RedisConnection connection(?string $name = null)
 * @method static bool ping(?string $connection = null)
 * @method static list<string> connections()
 * @method static \Pinoox\Component\Redis\RedisManager ___()
 *
 * @see \Pinoox\Component\Redis\RedisManager
 */
class Redis extends Portal
{
    public static function __register(): void
    {
        self::__bind(RedisManager::class)->setFactory([RedisManager::class, 'fromConfig']);
    }

    public static function __name(): string
    {
        return 'redis';
    }

    public static function __callback(): array
    {
        return [
            'connection',
            'ping',
            'connections',
        ];
    }

    public static function __exclude(): array
    {
        return [];
    }
}

