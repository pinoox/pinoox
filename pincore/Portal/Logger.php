<?php

namespace Pinoox\Portal;

use Monolog\Logger as MonologLogger;
use Pinoox\Component\Log\Manager;
use Pinoox\Component\Source\Portal;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Unified logging for Pinoox core and apps (Monolog + PSR-3).
 *
 * @method static Manager channel(string $name)
 * @method static Manager withContext(array $context)
 * @method static string path()
 * @method static MonologLogger getMonolog()
 * @method static void emergency(string|Stringable $message, array $context = [])
 * @method static void alert(string|Stringable $message, array $context = [])
 * @method static void critical(string|Stringable $message, array $context = [])
 * @method static void error(string|Stringable $message, array $context = [])
 * @method static void warning(string|Stringable $message, array $context = [])
 * @method static void notice(string|Stringable $message, array $context = [])
 * @method static void info(string|Stringable $message, array $context = [])
 * @method static void debug(string|Stringable $message, array $context = [])
 * @method static void log($level, string|Stringable $message, array $context = [])
 * @method static Manager ___()
 *
 * @see Manager
 */
class Logger extends Portal
{
    public static function __register(): void
    {
        self::__bind(Manager::class);
        static::__container()->setAlias(LoggerInterface::class, self::__id());
    }

    public static function __name(): string
    {
        return 'logger';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [
            'log',
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];
    }
}

