<?php

declare(strict_types=1);

namespace App\Domain;

class TimeFormatTools
{
    public static function guessTimeStampFormat(string $timestamp): TimeFormat
    {
        $currentTime = time();
        $currentTimeLen = strlen((string) $currentTime);

        $strTime = (string) floor((float) $timestamp);
        $timeStampLen = strlen($strTime);

        if (str_contains($timestamp, '.') && $timeStampLen === $currentTimeLen) {
            return TimeFormat::DotMilliseconds;
        }

        if ($currentTimeLen === $timeStampLen) {
            return TimeFormat::Seconds;
        }

        if (($currentTimeLen + 3) === $timeStampLen) {
            return TimeFormat::IntMilliseconds;
        }

        throw new \Exception('Missing case at '.__CLASS__);
    }

    /**
     * Result depends on timeformat and must be expressed in same format.
     */
    public static function scale(int|float $start, int|float $end, TimeFormat $timeFormat, ?int $stepSize = 1): array
    {
        $start = match ($timeFormat->value) {
            'DotMilliseconds', 'Seconds' => floor($start),
            'IntMilliseconds' => floor($start / 1000) * 1000,
        };
        $end = match ($timeFormat->value) {
            'DotMilliseconds', 'Seconds' => floor($end),
            'IntMilliseconds' => floor($end / 1000) * 1000,
        };

        $step = $stepSize * match ($timeFormat->value) {
            'DotMilliseconds', 'Seconds' => 1,
            'IntMilliseconds' => 1000,
        };

        return [new \ArrayIterator(range(
            start: (int) $start,
            end: (int) $end,
            step: (int) $step
        )), $step];
    }
}
