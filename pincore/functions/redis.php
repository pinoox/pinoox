<?php

use Pinoox\Portal\Redis;

if (!function_exists('redis')) {
    /**
     * Get a Redis connection or run a command on the default connection.
     */
    function redis(?string $connection = null): \Pinoox\Component\Redis\RedisConnection|\Pinoox\Component\Redis\RedisManager
    {
        if ($connection === null) {
            return Redis::___();
        }

        return Redis::connection($connection);
    }
}

