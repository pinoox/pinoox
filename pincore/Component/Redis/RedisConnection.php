<?php

namespace Pinoox\Component\Redis;

use Pinoox\Component\Redis\Connector\ConnectorInterface;
use Pinoox\Component\Redis\Connector\PhpRedisConnector;
use Pinoox\Component\Redis\Connector\PredisConnector;
use Predis\Client as PredisClient;

class RedisConnection
{
    public function __construct(
        private readonly object $client,
        private readonly string $driver,
    ) {
    }

    public function client(): object
    {
        return $this->client;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function ping(): bool
    {
        $response = $this->command('ping');

        if ($response === true) {
            return true;
        }

        if (is_string($response) && strtoupper($response) === 'PONG') {
            return true;
        }

        return (bool) $response;
    }

    public function get(string $key): ?string
    {
        $value = $this->command('get', [$key]);

        if ($value === false || $value === null) {
            return null;
        }

        return (string) $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl !== null && $ttl > 0) {
            return (bool) $this->command('setex', [$key, $ttl, $this->encode($value)]);
        }

        return (bool) $this->command('set', [$key, $this->encode($value)]);
    }

    public function delete(string ...$keys): int
    {
        if ($keys === []) {
            return 0;
        }

        return (int) $this->command('del', $keys);
    }

    public function exists(string $key): bool
    {
        return (bool) $this->command('exists', [$key]);
    }

    public function expire(string $key, int $seconds): bool
    {
        return (bool) $this->command('expire', [$key, $seconds]);
    }

    public function ttl(string $key): int
    {
        return (int) $this->command('ttl', [$key]);
    }

    public function incr(string $key, int $by = 1): int
    {
        if ($by === 1) {
            return (int) $this->command('incr', [$key]);
        }

        return (int) $this->command('incrBy', [$key, $by]);
    }

    public function decr(string $key, int $by = 1): int
    {
        if ($by === 1) {
            return (int) $this->command('decr', [$key]);
        }

        return (int) $this->command('decrBy', [$key, $by]);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $this->decode($cached);
        }

        $value = $callback($this);
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * @param list<mixed> $arguments
     */
    public function command(string $name, array $arguments = []): mixed
    {
        if ($this->client instanceof PredisClient) {
            return $this->client->__call($name, $arguments);
        }

        if ($this->client instanceof \Redis) {
            $method = $this->phpRedisMethod($name);

            return $this->client->{$method}(...$arguments);
        }

        if (is_callable([$this->client, $name])) {
            return $this->client->{$name}(...$arguments);
        }

        throw new \BadMethodCallException(sprintf('Redis command "%s" is not supported.', $name));
    }

    private function phpRedisMethod(string $name): string
    {
        return match (strtolower($name)) {
            'delete' => 'del',
            default => $name,
        };
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->command($name, $arguments);
    }

    private function encode(mixed $value): string
    {
        return is_string($value) ? $value : serialize($value);
    }

    private function decode(string $value): mixed
    {
        if ($value === '') {
            return $value;
        }

        $unserialized = @unserialize($value, ['allowed_classes' => true]);

        return $unserialized === false && $value !== 'b:0;' ? $value : $unserialized;
    }
}

