<?php

namespace Pinoox\Component\Log;

use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Stringable;

class Manager implements LoggerInterface
{
    /** @var array<string, self> */

    private static array $channels = [];

    private MonologLogger $monolog;
    private string $channel;
    /** @var array<string, mixed> */
    private array $context;

    public function __construct(?string $channel = null, array $context = [])
    {
        $config = LogConfig::resolve();
        $this->channel = $channel ?? LogConfig::channelName();
        $this->context = $context;
        $this->monolog = LogFactory::make($this->channel, $config);
    }

    public function channel(string $name): self
    {
        $key = LogConfig::channelName($name);

        if (!isset(self::$channels[$key])) {
            self::$channels[$key] = new self($key, $this->context);
        }

        return self::$channels[$key];
    }

    public function withContext(array $context): self
    {
        return new self($this->channel, array_merge($this->context, $context));
    }

    public static function path(): string
    {
        return LogConfig::path();
    }

    public function getMonolog(): MonologLogger
    {
        return $this->monolog;
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->monolog->log(
            is_string($level) ? LogConfig::parseLevel($level) : $level,
            (string) $message,
            array_merge($this->context, $context),
        );
    }
}

