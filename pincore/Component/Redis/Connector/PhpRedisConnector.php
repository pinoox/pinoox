<?php

namespace Pinoox\Component\Redis\Connector;

class PhpRedisConnector implements ConnectorInterface
{
    public function connect(array $config): object
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('The phpredis extension is not installed. Use REDIS_CLIENT=predis or install ext-redis.');
        }

        /** @var \Redis $client */
        $client = new \Redis();
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 6379);
        $timeout = (float) ($config['timeout'] ?? 1.0);
        $persistent = (bool) ($config['persistent'] ?? false);
        $persistentId = (string) ($config['persistent_id'] ?? 'pinoox');

        $connected = $persistent
            ? $client->pconnect($host, $port, $timeout, $persistentId)
            : $client->connect($host, $port, $timeout);

        if (!$connected) {
            throw new \RuntimeException(sprintf('Could not connect to Redis at %s:%d.', $host, $port));
        }

        if (!empty($config['password'])) {
            $client->auth((string) $config['password']);
        }

        $database = (int) ($config['database'] ?? 0);
        if ($database > 0) {
            $client->select($database);
        }

        if (!empty($config['read_timeout'])) {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, (string) $config['read_timeout']);
        }

        if (!empty($config['prefix'])) {
            $client->setOption(\Redis::OPT_PREFIX, (string) $config['prefix']);
        }

        return $client;
    }
}

