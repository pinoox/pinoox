<?php

use Pinoox\Component\Date\JalaliDate;
use Pinoox\Portal\Date;

if (!function_exists('now')) {
    function now(DateTimeZone|string|null $tz = null)
    {
        return Date::now($tz);
    }
}

if (!function_exists('today')) {
    function today(DateTimeZone|string|null $tz = null)
    {
        return Date::today($tz);
    }
}

if (!function_exists('carbon')) {
    function carbon(mixed $time = null, DateTimeZone|string|null $timezone = null)
    {
        return Date::parse($time, $timezone);
    }
}

if (!function_exists('jalali')) {
    function jalali(mixed $time = null, DateTimeZone|string|null $timezone = null): JalaliDate
    {
        return Date::jalali($time, $timezone);
    }
}

if (!function_exists('format_date')) {
    function format_date(mixed $time = null, ?string $format = null, ?string $calendar = null): string
    {
        return Date::format($time, $format, $calendar);
    }
}

if (!function_exists('jformat')) {
    function jformat(mixed $time = null, ?string $format = null, DateTimeZone|string|null $timezone = null): string
    {
        $format ??= Date::formatKey('datetime', 'jalali');

        return Date::jalali($time, $timezone)->format($format);
    }
}

if (!function_exists('format_jalali')) {
    function format_jalali(mixed $time = null, ?string $format = null, DateTimeZone|string|null $timezone = null): string
    {
        return jformat($time, $format, $timezone);
    }
}

if (!function_exists('gdate')) {
    function gdate(mixed $time = null, ?string $format = null, DateTimeZone|string|null $timezone = null): string
    {
        $format ??= Date::formatKey('datetime', 'gregorian');

        return Date::parse($time, $timezone)->format($format);
    }
}

if (!function_exists('date_format_smart')) {
    function date_format_smart(mixed $time = null, ?string $format = null): string
    {
        return Date::format($time, $format);
    }
}

if (!function_exists('date_ago')) {
    function date_ago(mixed $time = null, bool|int $exactAfterDays = false, ?string $format = null): ?string
    {
        return Date::approximateDate($time ?? 'now', $exactAfterDays, $format ?? Date::formatKey('date'));
    }
}

