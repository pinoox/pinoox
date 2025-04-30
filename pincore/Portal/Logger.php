<?php

namespace Pinoox\Portal;

use DateTimeZone as ObjectPortal3;
use Monolog\Handler\HandlerInterface as ObjectPortal1;
use Monolog\Handler\StreamHandler;
use Monolog\Level as ObjectPortal2;
use Monolog\Logger as MonologLogger;
use Pinoox\Component\Source\Portal;
use Psr\Log\LoggerInterface;

/**
 * @method static string getName()
 * @method static MonologLogger withName(string $name)
 * @method static MonologLogger pushHandler(\Monolog\Handler\HandlerInterface $handler)
 * @method static ObjectPortal1 popHandler()
 * @method static MonologLogger setHandlers(array $handlers)
 * @method static array getHandlers()
 * @method static MonologLogger pushProcessor(\Monolog\Processor\ProcessorInterface|callable $callback)
 * @method static callable popProcessor()
 * @method static array getProcessors()
 * @method static MonologLogger useMicrosecondTimestamps(bool $micro)
 * @method static MonologLogger useLoggingLoopDetection(bool $detectCycles)
 * @method static bool addRecord(\Monolog\Level|int $level, string $message, array $context = [], ?Monolog\JsonSerializableDateTimeImmutable $datetime = NULL)
 * @method static Logger close()
 * @method static Logger reset()
 * @method static string getLevelName(\Monolog\Level|int $level)
 * @method static ObjectPortal2 toMonologLevel(\Monolog\Level|int|string $level)
 * @method static bool isHandling(\Monolog\Level|int|string $level)
 * @method static MonologLogger setExceptionHandler(?Closure $callback)
 * @method static \Closure|null getExceptionHandler()
 * @method static Logger log($level, \Stringable|string $message, array $context = [])
 * @method static Logger debug(\Stringable|string $message, array $context = [])
 * @method static Logger info(\Stringable|string $message, array $context = [])
 * @method static Logger notice(\Stringable|string $message, array $context = [])
 * @method static Logger warning(\Stringable|string $message, array $context = [])
 * @method static Logger error(\Stringable|string $message, array $context = [])
 * @method static Logger critical(\Stringable|string $message, array $context = [])
 * @method static Logger alert(\Stringable|string $message, array $context = [])
 * @method static Logger emergency(\Stringable|string $message, array $context = [])
 * @method static MonologLogger setTimezone(\DateTimeZone $tz)
 * @method static ObjectPortal3 getTimezone()
 * @method static \Monolog\Logger ___()
 *
 * @see \Monolog\Logger
 */
class Logger extends Portal
{
	public static function __register(): void
	{
		// Load logger configuration
		$config = Config::file('pinoox')->get('log');

		$path = $config['path'] ?? sys_get_temp_dir() . '/pinoox.log';
		$channel = $config['channel'] ?? 'app';
		$level = $config['level'] ?? MonologLogger::DEBUG;

		// Register Monolog as the logger service
		self::__bind(MonologLogger::class)
		    ->setArguments([$channel])
		    ->addMethodCall('pushHandler', [new StreamHandler($path, $level)]);

		// Alias the PSR-3 LoggerInterface to our logger service
		static::__container()->setAlias(LoggerInterface::class, self::__id());
	}


	public static function __name(): string
	{
		return 'logger';
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
			'close',
			'reset',
			'log',
			'debug',
			'info',
			'notice',
			'warning',
			'error',
			'critical',
			'alert',
			'emergency'
		];
	}
}
