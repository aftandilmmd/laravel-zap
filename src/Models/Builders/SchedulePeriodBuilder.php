<?php

namespace Zap\Models\Builders;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use PDO;

class SchedulePeriodBuilder extends Builder
{
    /**
     * Scope a query to only include available periods.
     */
    public function available(): SchedulePeriodBuilder
    {
        return $this->where('is_available', true);
    }

    /**
     * Scope a query to only include periods for a specific date.
     */
    public function forDate(string $date): SchedulePeriodBuilder
    {
        return $this->where('date', Carbon::parse($date));
    }

    /**
     * Scope a query to only include periods within a time range.
     */
    public function forTimeRange(string $startTime, string $endTime): SchedulePeriodBuilder
    {
        return $this->where('start_time', '>=', $startTime)
            ->where('end_time', '<=', $endTime);
    }

    /**
     * Scope a query to find overlapping periods.
     */
    public function overlapping(string $date, string $startTime, string $endTime, ?CarbonInterface $endDate = null): SchedulePeriodBuilder
    {
        // Normalize input times to HH:MM format
        $startTime = str_pad($startTime, 5, '0', STR_PAD_LEFT);
        $endTime = str_pad($endTime, 5, '0', STR_PAD_LEFT);

        // Apply date filter
        $this->when(is_null($endDate), fn ($q) => $q->whereDate('date', $date));

        // Apply time overlap logic based on database driver

        /** @var Connection $connection */
        $connection = $this->getConnection();
        $driver = $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return $this->applySqliteTimeOverlap($this, $startTime, $endTime);
        }

        if ($driver === 'pgsql') {
            return $this->applyPostgresTimeOverlap($this, $startTime, $endTime);
        }

        return $this->applyStandardTimeOverlap($this, $startTime, $endTime);
    }

    /**
     * Apply SQLite-specific time overlap conditions (overnight-aware).
     */
    private function applySqliteTimeOverlap(SchedulePeriodBuilder $query, string $startTime, string $endTime): SchedulePeriodBuilder
    {
        $normalizeStart = 'CASE WHEN LENGTH(start_time) = 4 THEN "0" || start_time ELSE start_time END';
        $normalizeEnd = 'CASE WHEN LENGTH(end_time) = 4 THEN "0" || end_time ELSE end_time END';

        return $query->where(function ($q) use ($normalizeStart, $normalizeEnd, $startTime, $endTime) {
            // Case 1: Normal period (start < end) — standard overlap
            $q->where(function ($q2) use ($normalizeStart, $normalizeEnd, $startTime, $endTime) {
                $q2->whereRaw("$normalizeStart < $normalizeEnd")
                    ->whereRaw("$normalizeStart < ?", [$endTime])
                    ->whereRaw("$normalizeEnd > ?", [$startTime]);
            })
            // Case 2: Overnight period (start >= end) — overlaps if NOT fully outside
            ->orWhere(function ($q2) use ($normalizeStart, $normalizeEnd, $startTime, $endTime) {
                $q2->whereRaw("$normalizeStart >= $normalizeEnd")
                    ->where(function ($q3) use ($normalizeStart, $normalizeEnd, $startTime, $endTime) {
                        $q3->whereRaw("$normalizeStart < ?", [$endTime])
                            ->orWhereRaw("$normalizeEnd > ?", [$startTime]);
                    });
            });
        });
    }

    /**
     * Apply standard SQL time overlap conditions — MySQL (overnight-aware).
     */
    private function applyStandardTimeOverlap(SchedulePeriodBuilder $query, string $startTime, string $endTime): SchedulePeriodBuilder
    {
        $ns = "LPAD(start_time, 5, '0')";
        $ne = "LPAD(end_time, 5, '0')";

        return $query->where(function ($q) use ($ns, $ne, $startTime, $endTime) {
            // Case 1: Normal period
            $q->where(function ($q2) use ($ns, $ne, $startTime, $endTime) {
                $q2->whereRaw("$ns < $ne")
                    ->whereRaw("$ns < ?", [$endTime])
                    ->whereRaw("$ne > ?", [$startTime]);
            })
            // Case 2: Overnight period
            ->orWhere(function ($q2) use ($ns, $ne, $startTime, $endTime) {
                $q2->whereRaw("$ns >= $ne")
                    ->where(function ($q3) use ($ns, $ne, $startTime, $endTime) {
                        $q3->whereRaw("$ns < ?", [$endTime])
                            ->orWhereRaw("$ne > ?", [$startTime]);
                    });
            });
        });
    }

    /**
     * Apply PostgreSQL-specific time overlap conditions (overnight-aware).
     */
    private function applyPostgresTimeOverlap(SchedulePeriodBuilder $query, string $startTime, string $endTime): SchedulePeriodBuilder
    {
        $ns = "LPAD(start_time::text, 5, '0')";
        $ne = "LPAD(end_time::text, 5, '0')";

        return $query->where(function ($q) use ($ns, $ne, $startTime, $endTime) {
            // Case 1: Normal period
            $q->where(function ($q2) use ($ns, $ne, $startTime, $endTime) {
                $q2->whereRaw("$ns < $ne")
                    ->whereRaw("$ns < ?", [$endTime])
                    ->whereRaw("$ne > ?", [$startTime]);
            })
            // Case 2: Overnight period
            ->orWhere(function ($q2) use ($ns, $ne, $startTime, $endTime) {
                $q2->whereRaw("$ns >= $ne")
                    ->where(function ($q3) use ($ns, $ne, $startTime, $endTime) {
                        $q3->whereRaw("$ns < ?", [$endTime])
                            ->orWhereRaw("$ne > ?", [$startTime]);
                    });
            });
        });
    }
}
