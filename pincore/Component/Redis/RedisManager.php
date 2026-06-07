<?php

namespace Pinoox\Component\Redis;

use Pinoox\Component\Redis\Connector\ConnectorInterface;
use Pinoox\Component\Redis\Connector\PhpRedisConnector;
use Pinoox\Component\Redis\Connector\PredisConnector;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;

class RedisManager
{
    /** @var array<string, RedisConnection> */
    private array $connections = [];

    public function __construct(
        private readonly array $config,
    ) {
    }

    public static function fromConfig(?array $config = null): self
    {
        $config ??= Config::name('~redis')->get();

        return new self(is_array($config) ? $config : []);
    }

    public function connection(?string $name = null): RedisConnection
    {
        $name = $name ?: (string) ($this->config['default'] ?? 'default');

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    public function ping(?string $connection = null): bool
    {
        return $this->connection($connection)->ping();
    }

    /**
     * @return list<string>
     */
    public function connections(): array
    {
        return array_keys($this->config['connections'] ?? ['default' => []]);
    }

    private function createConnection(string $name): RedisConnection
    {
        $connections = $this->config['connections'] ?? [];
        $connectionConfig = $connections[$name] ?? null;

        if (!is_array($connectionConfig)) {
            throw new \InvalidArgumentException(sprintf('Redis connection "%s" is not defined.', $name));
        }

        $connectionConfig = $this->applyAppPrefix($connectionConfig);
        $driver = $this->resolveDriver($connectionConfig);
        $connector = $this->connector($driver);
        $client = $connector->connect($connectionConfig);

        return new RedisConnection($client, $driver);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function applyAppPrefix(array $config): array
    {
        try {
            $package = App::package();
        } catch (\Throwable) {
            return $config;
        }

        if ($package === '' || $package === '~') {
            return $config;
        }

        $appPrefix = App::config()->get('redis.prefix');
        if (!is_string($appPrefix) || $appPrefix === '') {
            return $config;
        }

        $basePrefix = (string) ($config['prefix'] ?? ($this->config['prefix'] ?? ''));
        $config['prefix'] = rtrim($basePrefix, ':') . ':' . trim($appPrefix, ':') . ':';

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveDriver(array $config): string
    {
        $driver = strtolower((string) ($config['driver'] ?? 'phpredis'));

        if ($driver === 'phpredis' && !extension_loaded('redis')) {
            return 'predis';
        }

        return $driver;
    }

    private function connector(string $driver): ConnectorInterface
    {
        return match ($driver) {
            'predis' => new PredisConnector(),
            'phpredis' => new PhpRedisConnector(),
            default => throw new \InvalidArgumentException(sprintf('Unsupported Redis driver "%s".', $driver)),
        };
    }
}

