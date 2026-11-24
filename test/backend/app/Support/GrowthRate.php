<?php

namespace App\Support;

class GrowthRate
{
    /**
     * Configured weekly percentage (e.g. 5.0 = 5% per 7 days).
     */
    public static function weeklyPercent(): float
    {
        return (float) config('growth.weekly_percent', 5.0);
    }

    /**
     * Daily percentage that compounds to exactly the weekly target over 7 days.
     *
     * (1 + weekly)^(1/7) - 1 — not simply weekly/7, which compounds to ~5.11%
     * per week when 5% is advertised.
     */
    public static function dailyPercent(?float $weeklyPercent = null): float
    {
        $weekly = ($weeklyPercent ?? self::weeklyPercent()) / 100;

        if ($weekly <= 0) {
            return 0.0;
        }

        return (pow(1 + $weekly, 1 / 7) - 1) * 100;
    }
}
