<?php

namespace Pinoox\Component\Date;

use Carbon\Carbon;
use Pinoox\Component\Lang;

class DateFormatter
{
    public static function approximate(
        Carbon|string|int|null $date,
        Carbon|string|int|null $reference = null,
        bool|string $exactAfterDays = false,
        string $dateFormat = 'Y/m/d',
        string $calendar = 'gregorian',
    ): ?string {
        $reference = self::toCarbon($reference ?? 'now');
        $target = self::toCarbon($date);

        if (!$target || !$reference) {
            return Lang::get('~date.invalid') ?: "Date isn't valid";
        }

        $seconds = abs($reference->getTimestamp() - $target->getTimestamp());
        $isPast = $target->lessThanOrEqualTo($reference);

        $units = [
            Lang::get('~date.year') => 365 * 86400,
            Lang::get('~date.month') => 30 * 86400,
            Lang::get('~date.week') => 7 * 86400,
            Lang::get('~date.day') => 86400,
            Lang::get('~date.hour') => 3600,
            Lang::get('~date.minute') => 60,
            Lang::get('~date.second') => 1,
        ];

        foreach ($units as $label => $length) {
            if ($seconds >= $length) {
                $value = (int) round($seconds / $length);
                $phrase = $value . ' ' . $label;

                if ($isPast) {
                    if ($length <= 60) {
                        return Lang::get('~date.some_sec_before');
                    }

                    if ($exactAfterDays !== false && $length >= 86400) {
                        $days = is_numeric($exactAfterDays) ? (int) $exactAfterDays : 7;
                        if ($seconds >= ($days * 86400)) {
                            return self::formatCarbon($target, $dateFormat, $calendar);
                        }
                    }

                    return $phrase . ' ' . Lang::get('~date.before');
                }

                return $phrase . ' ' . (Lang::get('~date.after') ?: 'later');
            }
        }

        return Lang::get('~date.some_sec_before');
    }

    public static function formatCarbon(Carbon $carbon, string $format, string $calendar = 'jalali'): string
    {
        if ($calendar === 'jalali') {
            return JalaliDate::make($carbon)->format($format);
        }

        return $carbon->format($format);
    }

    private static function toCarbon(Carbon|string|int|null $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

