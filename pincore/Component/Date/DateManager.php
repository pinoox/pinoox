<?php

namespace Pinoox\Component\Date;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Illuminate\Support\DateFactory;
use Morilog\Jalali\Jalalian;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;

class DateManager
{
    private DateFactory $factory;

    private ?string $calendarOverride = null;

    public function __construct(
        private array $config = [],
    ) {
        $this->factory = new DateFactory();
        $this->applyTimezone();
    }

    public static function fromConfig(?array $config = null): self
    {
        $config ??= Config::name('~date')->get();

        return new self(is_array($config) ? $config : []);
    }

    public function factory(): DateFactory
    {
        return $this->factory;
    }

    public function timezone(): string
    {
        try {
            $appTimezone = App::get('date.timezone');
            if (is_string($appTimezone) && $appTimezone !== '') {
                return $appTimezone;
            }
        } catch (\Throwable) {
        }

        return (string) ($this->config['timezone'] ?? 'UTC');
    }

    /**
     * Use a specific calendar for this manager instance (does not mutate the portal singleton).
     *
     * Date::usingCalendar('jalali')->format($time);
     */
    public function usingCalendar(string $calendar): self
    {
        $instance = clone $this;
        $instance->calendarOverride = $this->normalizeCalendar($calendar);

        return $instance;
    }

    public function calendar(): string
    {
        if ($this->calendarOverride !== null) {
            return $this->calendarOverride;
        }

        try {
            $appCalendar = App::get('date.calendar');
            if (is_string($appCalendar) && $appCalendar !== '') {
                return $this->normalizeCalendar($appCalendar);
            }
        } catch (\Throwable) {
        }

        try {
            $locale = (string) App::get('lang');
            $localeCalendar = $this->config['locale_calendar'][$locale] ?? null;
            if (is_string($localeCalendar) && $localeCalendar !== '') {
                return $this->normalizeCalendar($localeCalendar);
            }
        } catch (\Throwable) {
        }

        return $this->normalizeCalendar((string) ($this->config['calendar'] ?? 'gregorian'));
    }

    private function normalizeCalendar(string $calendar): string
    {
        return match (strtolower(trim($calendar))) {
            'jalali', 'jalaali', 'shamsi' => 'jalali',
            'gregorian', 'gregory', 'miladi', 'g' => 'gregorian',
            default => 'gregorian',
        };
    }

    public function isJalali(): bool
    {
        return $this->calendar() === 'jalali';
    }

    public function format(mixed $time = null, ?string $format = null, ?string $calendar = null): string
    {
        $calendar ??= $this->calendar();
        $format ??= $this->formatKey('datetime', $calendar);

        if ($calendar === 'jalali') {
            return $this->jalali($time)->format($format);
        }

        return $this->parse($time)->format($format);
    }

    public function formatKey(string $key, ?string $calendar = null): string
    {
        $calendar ??= $this->calendar();
        $formats = $this->config['formats'][$calendar] ?? [];

        return (string) ($formats[$key] ?? 'Y-m-d H:i:s');
    }

    public function now(DateTimeZone|string|null $tz = null): Carbon
    {
        return $this->factory->now($tz ?? $this->timezone());
    }

    public function today(DateTimeZone|string|null $tz = null): Carbon
    {
        return $this->factory->today($tz ?? $this->timezone());
    }

    public function parse(mixed $time = null, DateTimeZone|string|null $tz = null): Carbon
    {
        return $this->factory->parse($time ?? 'now', $tz ?? $this->timezone());
    }

    public function jalali(mixed $time = null, DateTimeZone|string|null $tz = null): JalaliDate
    {
        return JalaliDate::make($time ?? 'now', $tz ?? $this->timezone());
    }

    public function parseJalali(string $date, string $format = 'Y-m-d', DateTimeZone|string|null $tz = null): JalaliDate
    {
        return JalaliDate::parse($date, $format, $tz ?? $this->timezone());
    }

    public function validate(mixed $date, string $format = 'Y-m-d H:i:s'): bool
    {
        if (empty($date)) {
            return false;
        }

        $parsed = DateTime::createFromFormat($format, (string) $date);

        return $parsed instanceof DateTime && $parsed->format($format) === (string) $date;
    }

    /**
     * @return list<string>
     */
    public function betweenJDate(mixed $startDate, mixed $endDate, int $stepDays = 1, string $format = 'Y-m-d'): array
    {
        $begin = $this->parse($startDate);
        $end = $this->parse($endDate);
        $period = new DatePeriod($begin, new DateInterval('P' . max(1, $stepDays) . 'D'), $end);
        $result = [];

        foreach ($period as $date) {
            $result[] = JalaliDate::make($date)->format($format);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function betweenGDate(mixed $startDate, mixed $endDate, string $interval = '1D', string $format = 'Y-m-d'): array
    {
        $begin = new DateTime((string) $startDate);
        $end = new DateTime((string) $endDate);
        $period = new DatePeriod($begin, new DateInterval('P' . $interval), $end);
        $result = [];

        foreach ($period as $date) {
            $result[] = $date->format($format);
        }

        return $result;
    }

    public function compareBetween(
        mixed $date,
        mixed $startDate,
        mixed $endDate,
        bool $isJalali = false,
        string $format = 'Y/m/d H:i:s',
    ): bool {
        if ($isJalali) {
            $date = $this->g($format, $date, true);
        }

        return $this->compare($date, $startDate, '>=', $isJalali, $format)
            && $this->compare($date, $endDate, '<=', $isJalali, $format);
    }

    public function compare(
        mixed $date1,
        mixed $date2,
        string $operator,
        bool $isJalali = false,
        string $format = 'Y-m-d H:i:s',
    ): bool {
        if ($isJalali) {
            $date1 = $this->g($format, $date1, true);
            $date2 = $this->g($format, $date2, true);
        }

        $d1 = new DateTime((string) $date1);
        $d2 = new DateTime((string) $date2);

        return match ($operator) {
            '==' => $d1 == $d2,
            '!=' => $d1 != $d2,
            '>' => $d1 > $d2,
            '<' => $d1 < $d2,
            '<=' => $d1 <= $d2,
            '>=' => $d1 >= $d2,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public function getDaysWeek(mixed $date, bool $isJalali = false, string $format = 'Y-m-d'): array
    {
        if ($isJalali) {
            $base = $this->jalali($date);
            $weekDay = $base->inner()->getDayOfWeek();
            $result = [];

            for ($i = 0; $i <= 6; $i++) {
                $offset = $i - $weekDay;
                $carbon = $base->toCarbon();
                if ($offset !== 0) {
                    $offset > 0 ? $carbon->addDays($offset) : $carbon->subDays(abs($offset));
                }
                $result[$i] = JalaliDate::make($carbon)->format($format);
            }

            return $result;
        }

        $weekDay = (int) date('w', strtotime((string) $date));
        $result = [];

        for ($i = 0; $i <= 6; $i++) {
            $result[$i] = date($format, strtotime((string) $date . ' ' . ($i - $weekDay) . ' day'));
        }

        return $result;
    }

    public function dayOfWeek(mixed $date = null): ?string
    {
        $week = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        if ($date === null) {
            return strtolower(date('D'));
        }

        $index = (int) date('w', strtotime((string) $date));

        return $week[$index] ?? null;
    }

    public function approximateDate(
        mixed $date,
        bool|int $exactAfterDays = false,
        string $dateFormat = 'Y/m/d',
    ): ?string {
        return DateFormatter::approximate(
            $this->parse($date),
            $this->now(),
            $exactAfterDays,
            $dateFormat,
            $this->calendar(),
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->factory->{$name}(...$arguments);
    }

    private function applyTimezone(): void
    {
        $timezone = $this->timezone();
        if ($timezone !== '' && !ini_get('date.timezone')) {
            date_default_timezone_set($timezone);
        }
    }

    private function detectJalaliString(string $date): bool
    {
        if (!preg_match('/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $date, $matches)) {
            return false;
        }

        $year = (int) $matches[1];

        return $year >= 1200 && $year <= 1600;
    }

    private function extractDatePart(string $date): string
    {
        preg_match('/\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/', $date, $match);

        return $match[0] ?? $date;
    }
}

