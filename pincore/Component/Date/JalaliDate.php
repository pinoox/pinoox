<?php

namespace Pinoox\Component\Date;

use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\DateFactory;
use Morilog\Jalali\Jalalian;
use Pinoox\Component\Lang;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;

class JalaliDate
{
    public function __construct(
        private readonly Jalalian $inner,
    ) {
    }

    public static function now(DateTimeZone|string|null $timezone = null): self
    {
        $tz = self::resolveTimezone($timezone);

        return new self(Jalalian::now($tz));
    }

    public static function make(mixed $time = null, DateTimeZone|string|null $timezone = null): self
    {
        if ($time instanceof self) {
            return $time;
        }

        if ($time instanceof Jalalian) {
            return new self($time);
        }

        if ($time === null || $time === 'now') {
            return self::now($timezone);
        }

        $tz = self::resolveTimezone($timezone);

        if ($time instanceof Carbon) {
            return new self(Jalalian::fromCarbon($time));
        }

        if ($time instanceof DateTimeInterface) {
            return new self(Jalalian::fromDateTime($time, $tz));
        }

        if (is_int($time)) {
            return new self(Jalalian::fromDateTime($time, $tz));
        }

        return new self(Jalalian::fromDateTime((string) $time, $tz));
    }

    public static function parse(string $date, string $format = 'Y-m-d', DateTimeZone|string|null $timezone = null): self
    {
        return new self(Jalalian::fromFormat($format, $date, self::resolveTimezone($timezone)));
    }

    public function format(string $format): string
    {
        return $this->inner->format($format);
    }

    public function toCarbon(): Carbon
    {
        return $this->inner->toCarbon();
    }

    public function toGregorian(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->toCarbon()->format($format);
    }

    public function timestamp(): int
    {
        return $this->inner->getTimestamp();
    }

    public function ago(): string
    {
        return DateFormatter::approximate($this->toCarbon(), calendar: 'jalali');
    }

    public function diffForHumans(?Carbon $other = null): string
    {
        return DateFormatter::approximate($this->toCarbon(), $other, calendar: 'jalali');
    }

    public function inner(): Jalalian
    {
        return $this->inner;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->inner->{$name}(...$arguments);

        return $result instanceof Jalalian ? new self($result) : $result;
    }

    public function __toString(): string
    {
        return $this->inner->format('Y/m/d H:i:s');
    }

    private static function resolveTimezone(DateTimeZone|string|null $timezone): ?DateTimeZone
    {
        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }

        if (is_string($timezone) && $timezone !== '') {
            return new DateTimeZone($timezone);
        }

        return null;
    }
}

