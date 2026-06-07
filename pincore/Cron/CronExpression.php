<?php

namespace Pinoox\Cron;

use DateTimeImmutable;

class CronExpression
{
    public function __construct(private readonly string $expression)
    {
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function isDue(?DateTimeImmutable $date = null): bool
    {
        $date ??= new DateTimeImmutable();
        $parts = preg_split('/\s+/', trim($this->expression));

        if (!is_array($parts) || count($parts) !== 5) {
            return false;
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        return $this->matches($minute, (int)$date->format('i'), 0, 59)
            && $this->matches($hour, (int)$date->format('G'), 0, 23)
            && $this->matches($day, (int)$date->format('j'), 1, 31)
            && $this->matches($month, (int)$date->format('n'), 1, 12)
            && $this->matches($weekday, (int)$date->format('w'), 0, 6);
    }

    private function matches(string $expression, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $expression) as $part) {
            if ($this->matchesPart(trim($part), $value, $min, $max)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPart(string $part, int $value, int $min, int $max): bool
    {
        if ($part === '*') {
            return true;
        }

        $step = 1;
        if (str_contains($part, '/')) {
            [$part, $stepValue] = explode('/', $part, 2);
            $step = max(1, (int)$stepValue);
        }

        if ($part === '*') {
            return (($value - $min) % $step) === 0;
        }

        if (str_contains($part, '-')) {
            [$start, $end] = array_map('intval', explode('-', $part, 2));
            return $value >= $start && $value <= $end && (($value - $start) % $step) === 0;
        }

        return $value === (int)$part;
    }
}

