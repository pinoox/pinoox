<?php

namespace Pinoox\Component\Log;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class LogFactory
{
    /**
     * @param array{
     *     path: string,
     *     level: \Monolog\Level,
     *     rotate?: bool,
     *     max_files?: int
     * } $config
     */
    public static function make(string $channel, array $config): MonologLogger
    {
        $logger = new MonologLogger($channel);

        $handler = ($config['rotate'] ?? true)
            ? new RotatingFileHandler(
                $config['path'],
                $config['max_files'] ?? 14,
                $config['level'],
                true,
                0644,
            )
            : new StreamHandler($config['path'], $config['level']);

        $logger->pushHandler($handler);
        $logger->pushProcessor(new ContextProcessor());

        return $logger;
    }
}

