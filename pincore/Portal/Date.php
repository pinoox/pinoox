<?php

namespace Pinoox\Portal;

use Carbon\Carbon;
use Illuminate\Support\DateFactory;
use Pinoox\Component\Date\DateManager;
use Pinoox\Component\Date\JalaliDate;
use Pinoox\Component\Source\Portal;

/**
 * @method static Carbon now(DateTimeZone|string|null $tz = null)
 * @method static Carbon today(DateTimeZone|string|null $tz = null)
 * @method static Carbon parse(mixed $time = null, DateTimeZone|string|null $tz = null)
 * @method static JalaliDate jalali(mixed $time = null, DateTimeZone|string|null $tz = null)
 * @method static JalaliDate parseJalali(string $date, string $format = 'Y-m-d', DateTimeZone|string|null $tz = null)
 * @method static string format(mixed $time = null, ?string $format = null, ?string $calendar = null)
 * @method static string formatKey(string $key, ?string $calendar = null)
 * @method static string timezone()
 * @method static string calendar()
 * @method static DateManager usingCalendar(string $calendar)
 * @method static bool isJalali()
 * @method static bool validate(mixed $date, string $format = 'Y-m-d H:i:s')
 * @method static array betweenJDate(mixed $startDate, mixed $endDate, int $stepDays = 1, string $format = 'Y-m-d')
 * @method static array betweenGDate(mixed $startDate, mixed $endDate, string $interval = '1D', string $format = 'Y-m-d')
 * @method static bool compareBetween(mixed $date, mixed $startDate, mixed $endDate, bool $isJalali = false, string $format = 'Y/m/d H:i:s')
 * @method static bool compare(mixed $date1, mixed $date2, string $operator, bool $isJalali = false, string $format = 'Y-m-d H:i:s')
 * @method static array getDaysWeek(mixed $date, bool $isJalali = false, string $format = 'Y-m-d')
 * @method static string|null dayOfWeek(mixed $date = null)
 * @method static string|null approximateDate(mixed $date, bool|int $exactAfterDays = false, string $dateFormat = 'Y/m/d')
 * @method static DateFactory factory()
 * @method static DateManager ___()
 *
 * @see \Pinoox\Component\Date\DateManager
 */
class Date extends Portal
{
    public static function __register(): void
    {
        self::__bind(DateManager::class)->setFactory([DateManager::class, 'fromConfig']);
    }

    public static function __name(): string
    {
        return 'date';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function usingCalendar(string $calendar): DateManager
    {
        return self::___()->usingCalendar($calendar);
    }

    public static function __callback(): array
    {
        return [];
    }
}

