<?php

namespace Pinoox\Component\Redis\Cache;

use Pinoox\Component\Redis\RedisConnection;
use Psr\SimpleCache\CacheInterface;

class RedisCacheStore implements CacheInterface
{
    public function __construct(
        private readonly RedisConnection $redis,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);

        if ($value === null) {
            return $default;
        }

        return $this->decode($value);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->redis->set($key, $this->encode($value), $this->normalizeTtl($ttl));
    }

    public function delete(string $key): bool
    {
        return $this->redis->delete($key) > 0;
    }

    public function clear(): bool
    {
        if (method_exists($this->redis->client(), 'flushDB')) {
            return (bool) $this->redis->command('flushDB');
        }

        return false;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get((string) $key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $ok = true;

        foreach ($values as $key => $value) {
            $ok = $this->set((string) $key, $value, $ttl) && $ok;
        }

        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $list = [];

        foreach ($keys as $key) {
            $list[] = (string) $key;
        }

        return $this->redis->delete(...$list) >= 0;
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key);
    }

    private function encode(mixed $value): string
    {
        return serialize($value);
    }

    private function decode(string $value): mixed
    {
        $decoded = @unserialize($value, ['allowed_classes' => true]);

        return $decoded === false && $value !== 'b:0;' ? $value : $decoded;
    }

    private function normalizeTtl(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            return (new \DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }

        return max(0, (int) $ttl);
    }
}

