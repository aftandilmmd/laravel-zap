<?php

namespace Zap\Helper;

use Carbon\Carbon;

class TimeHelper
{
    /**
     * Check if a time period crosses midnight (overnight).
     * e.g., start=22:00, end=02:00 → true
     */
    public static function isOvernight(string $startTime, string $endTime): bool
    {
        return $endTime <= $startTime;
    }

    /**
     * Calculate duration in minutes, handling overnight periods.
     * e.g., 22:00→02:00 = 240 minutes (4 hours)
     */
    public static function durationInMinutes(string $startTime, string $endTime): int
    {
        $s = self::timeToMinutes($startTime);
        $e = self::timeToMinutes($endTime);

        if ($e <= $s) {
            $e += 1440; // Add 24 hours
        }

        return $e - $s;
    }

    /**
     * Check if two time periods overlap, handling overnight periods.
     *
     * For overnight periods (e.g., 22:00→02:00), we normalize both periods
     * and check overlap in both original and +24h shifted positions to handle
     * cases like: period1=22:00→02:00 overlapping with period2=01:00→01:30.
     */
    public static function periodsOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        // Normalize to HH:MM
        $start1 = substr($start1, 0, 5);
        $end1 = substr($end1, 0, 5);
        $start2 = substr($start2, 0, 5);
        $end2 = substr($end2, 0, 5);

        $s1 = self::timeToMinutes($start1);
        $e1 = self::timeToMinutes($end1);
        $s2 = self::timeToMinutes($start2);
        $e2 = self::timeToMinutes($end2);

        // Normalize overnight periods
        if ($e1 <= $s1) {
            $e1 += 1440;
        }
        if ($e2 <= $s2) {
            $e2 += 1440;
        }

        // Check overlap in original position
        if ($s1 < $e2 && $e1 > $s2) {
            return true;
        }

        // Check with period1 shifted +1440 (handles: early morning period1 vs overnight period2)
        if (($s1 + 1440) < $e2 && ($e1 + 1440) > $s2) {
            return true;
        }

        // Check with period2 shifted +1440 (handles: overnight period1 vs early morning period2)
        if ($s1 < ($e2 + 1440) && $e1 > ($s2 + 1440)) {
            return true;
        }

        return false;
    }

    /**
     * Check if two time periods overlap with a buffer, handling overnight periods.
     */
    public static function periodsOverlapWithBuffer(
        string $start1,
        string $end1,
        string $start2,
        string $end2,
        int $bufferMinutes = 0
    ): bool {
        if ($bufferMinutes <= 0) {
            return self::periodsOverlap($start1, $end1, $start2, $end2);
        }

        $s1 = self::timeToMinutes($start1);
        $e1 = self::timeToMinutes($end1);
        $s2 = self::timeToMinutes($start2);
        $e2 = self::timeToMinutes($end2);

        // Normalize overnight periods
        if ($e1 <= $s1) {
            $e1 += 1440;
        }
        if ($e2 <= $s2) {
            $e2 += 1440;
        }

        // Apply buffer to first period
        $s1 -= $bufferMinutes;
        $e1 += $bufferMinutes;

        // Check overlap in original position
        if ($s1 < $e2 && $e1 > $s2) {
            return true;
        }

        // Check with period1 shifted +1440
        if (($s1 + 1440) < $e2 && ($e1 + 1440) > $s2) {
            return true;
        }

        // Check with period2 shifted +1440
        if ($s1 < ($e2 + 1440) && $e1 > ($s2 + 1440)) {
            return true;
        }

        return false;
    }

    /**
     * Convert a time string (HH:MM) to total minutes from midnight.
     */
    public static function timeToMinutes(string $time): int
    {
        $parts = explode(':', substr($time, 0, 5));

        return (int) $parts[0] * 60 + (int) $parts[1];
    }
}
