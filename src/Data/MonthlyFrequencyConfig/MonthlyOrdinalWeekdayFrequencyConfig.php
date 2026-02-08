<?php

namespace Zap\Data\MonthlyFrequencyConfig;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Zap\Data\FrequencyConfig;
use Zap\Models\Schedule;

/**
 * Monthly recurrence on an ordinal weekday: 1st Wednesday, 2nd Friday, last Monday, etc.
 *
 * @property-read int $ordinal 1=first, 2=second, 3=third, 4=fourth, 5=last
 * @property-read int $day_of_week 0=Sunday, 1=Monday, ... 6=Saturday (Carbon)
 */
final class MonthlyOrdinalWeekdayFrequencyConfig extends FrequencyConfig
{
    /** @var int 1=first, 2=second, 3=third, 4=fourth, 5=last */
    private int $ordinal;

    /** @var int<0, 6> Carbon day of week */
    private int $dayOfWeek;

    public function __construct(
        int $ordinal,
        int $dayOfWeek,
    ) {
        if ($ordinal < 1 || $ordinal > 5) {
            throw new \InvalidArgumentException("Ordinal must be 1-5 (first, second, third, fourth, last), got {$ordinal}");
        }
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException("Day of week must be 0-6 (Sunday-Saturday), got {$dayOfWeek}");
        }
        $this->ordinal = $ordinal;
        $this->dayOfWeek = $dayOfWeek;
    }

    public static function fromArray(array $data): self
    {
        $ordinal = $data['ordinal'] ?? null;
        if ($ordinal === null) {
            throw new \InvalidArgumentException("Missing 'ordinal' key in MonthlyOrdinalWeekdayFrequencyConfig data array.");
        }
        if (is_string($ordinal) && strtolower($ordinal) === 'last') {
            $ordinal = 5;
        }
        $ordinal = (int) $ordinal;

        $dayOfWeek = $data['day_of_week'] ?? null;
        if ($dayOfWeek === null && isset($data['day'])) {
            $dayOfWeek = self::parseDayOfWeek($data['day']);
        }
        if ($dayOfWeek === null) {
            throw new \InvalidArgumentException("Missing 'day_of_week' or 'day' key in MonthlyOrdinalWeekdayFrequencyConfig data array.");
        }
        $dayOfWeek = is_string($dayOfWeek) ? self::parseDayOfWeek($dayOfWeek) : (int) $dayOfWeek;

        return new self(ordinal: $ordinal, dayOfWeek: $dayOfWeek);
    }

    public function getOrdinal(): int
    {
        return $this->ordinal;
    }

    /** @return int<0, 6> */
    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setStartFromStartDate(CarbonInterface $startDate): self
    {
        return $this;
    }

    public function getNextRecurrence(CarbonInterface $current): CarbonInterface
    {
        $nextMonth = $current->copy()->addMonth()->startOfMonth();
        $target = self::getOrdinalWeekdayInMonth($nextMonth->year, $nextMonth->month, $this->ordinal, $this->dayOfWeek);

        return $target->copy();
    }

    public function shouldCreateInstance(CarbonInterface $date): bool
    {
        return self::isOrdinalWeekdayInMonth($date, $this->ordinal, $this->dayOfWeek);
    }

    public function shouldCreateRecurringInstance(Schedule $schedule, CarbonInterface $date): bool
    {
        if ($date->lt($schedule->start_date->startOfDay())) {
            return false;
        }

        return self::isOrdinalWeekdayInMonth($date, $this->ordinal, $this->dayOfWeek);
    }

    public function toArray(): array
    {
        return [
            'ordinal' => $this->ordinal,
            'day_of_week' => $this->dayOfWeek,
        ];
    }

    /**
     * Parse day name or int to Carbon day of week (0-6).
     */
    private static function parseDayOfWeek(string|int $day): int
    {
        if (is_int($day)) {
            return $day >= 0 && $day <= 6 ? $day : 1;
        }

        return match (strtolower((string) $day)) {
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            default => Carbon::MONDAY,
        };
    }

    /**
     * Get the date of the Nth occurrence of a weekday in a given month (1=first, 5=last).
     */
    private static function getOrdinalWeekdayInMonth(int $year, int $month, int $ordinal, int $dayOfWeek): CarbonInterface
    {
        if ($ordinal === 5) {
            $start = Carbon::create($year, $month, 1)->endOfMonth();

            while ($start->dayOfWeek !== $dayOfWeek) {
                $start->subDay();
            }

            return $start;
        }

        $start = Carbon::create($year, $month, 1);
        while ($start->dayOfWeek !== $dayOfWeek) {
            $start->addDay();
        }

        return $start->addWeeks($ordinal - 1);
    }

    /**
     * Check if the given date is the Nth occurrence of the given weekday in its month.
     */
    private static function isOrdinalWeekdayInMonth(CarbonInterface $date, int $ordinal, int $dayOfWeek): bool
    {
        if ($date->dayOfWeek !== $dayOfWeek) {
            return false;
        }

        $expected = self::getOrdinalWeekdayInMonth(
            (int) $date->format('Y'),
            (int) $date->format('m'),
            $ordinal,
            $dayOfWeek
        );

        return $date->toDateString() === $expected->toDateString();
    }
}
