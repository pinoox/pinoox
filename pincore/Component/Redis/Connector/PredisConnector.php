<?php

namespace Pinoox\Component\Redis\Connector;

use Predis\Client;

class PredisConnector implements ConnectorInterface
{
    public function connect(array $config): object
    {
        if (!class_exists(Client::class)) {
            throw new \RuntimeException('Predis is not installed. Run: composer require predis/predis');
        }

        $parameters = [
            'scheme' => (string) ($config['scheme'] ?? 'tcp'),
            'host' => (string) ($config['host'] ?? '127.0.0.1'),
            'port' => (int) ($config['port'] ?? 6379),
            'database' => (int) ($config['database'] ?? 0),
            'timeout' => (float) ($config['timeout'] ?? 1.0),
            'read_write_timeout' => (float) ($config['read_timeout'] ?? 1.0),
        ];

        if (!empty($config['password'])) {
            $parameters['password'] = (string) $config['password'];
        }

        $options = [];

        if (!empty($config['prefix'])) {
            $options['prefix'] = (string) $config['prefix'];
        }

        if (!empty($config['persistent'])) {
            $options['persistent'] = true;
        }

        return new Client($parameters, $options);
    }
}

