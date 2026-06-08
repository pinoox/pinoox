<?php

use Pinoox\Component\Redis\Connector\PredisConnector;
use Pinoox\Component\Redis\RedisConnection;
use Pinoox\Component\Redis\RedisManager;
use Pinoox\Portal\Redis;

it('registers the Redis portal and helper', function () {
    expect(class_exists(Redis::class))->toBeTrue()
        ->and(function_exists('redis'))->toBeTrue();
});

it('declares the Redis portal contract', function () {
    expectPortalContract(Redis::class);
});

it('loads redis config from system config', function () {
    $config = require testProjectRoot() . '/pincore/config/redis.config.php';

    expect($config)->toHaveKeys(['default', 'prefix', 'connections'])
        ->and($config['connections'])->toHaveKeys(['default', 'cache']);
});

it('falls back to predis when phpredis is unavailable', function () {
    $manager = new RedisManager([
        'default' => 'default',
        'connections' => [
            'default' => [
                'driver' => 'phpredis',
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ],
    ]);

    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('resolveDriver');
    $method->setAccessible(true);

    $driver = $method->invoke($manager, ['driver' => 'phpredis']);

    if (extension_loaded('redis')) {
        expect($driver)->toBe('phpredis');
    } else {
        expect($driver)->toBe('predis');
    }
});

it('wraps predis client commands', function () {
    if (!class_exists(\Predis\Client::class)) {
        $this->markTestSkipped('Predis is not installed.');
    }

    $client = new class {
        public array $calls = [];

        public function ping(): string
        {
            $this->calls[] = 'ping';

            return 'PONG';
        }

        public function set($key, $value): bool
        {
            $this->calls[] = ['set', $key, $value];

            return true;
        }

        public function get($key): ?string
        {
            return $key === 'foo' ? 'bar' : null;
        }
    };

    $connection = new RedisConnection($client, 'predis');

    expect($connection->ping())->toBeTrue()
        ->and($connection->set('foo', 'bar'))->toBeTrue()
        ->and($connection->get('foo'))->toBe('bar');
});

it('builds manager connection list from config', function () {
    $manager = new RedisManager([
        'default' => 'default',
        'connections' => [
            'default' => ['driver' => 'predis', 'host' => '127.0.0.1'],
            'cache' => ['driver' => 'predis', 'host' => '127.0.0.1', 'database' => 1],
        ],
    ]);

    expect($manager->connections())->toBe(['default', 'cache']);
});

it('creates predis connector with prefix option', function () {
    if (!class_exists(\Predis\Client::class)) {
        $this->markTestSkipped('Predis is not installed.');
    }

    $connector = new PredisConnector();
    $client = $connector->connect([
        'host' => '127.0.0.1',
        'port' => 6379,
        'prefix' => 'test:',
    ]);

    expect($client)->toBeInstanceOf(\Predis\Client::class);
});

it('documents redis cache store in cache config', function () {
    $config = require testProjectRoot() . '/pincore/config/cache.config.php';

    expect($config['stores'])->toHaveKey('redis')
        ->and($config['stores']['redis']['driver'])->toBe('redis');
});

