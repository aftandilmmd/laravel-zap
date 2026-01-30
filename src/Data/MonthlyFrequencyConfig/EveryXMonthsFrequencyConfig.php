<?php

namespace Zap\Data\MonthlyFrequencyConfig;

use Carbon\CarbonInterface;
use Zap\Data\FrequencyConfig;
use Zap\Models\Schedule;

/**
 * Generic monthly frequency config that supports any number of months (1-12).
 * Uses instance-based frequency instead of static method.
 *
 * @property-read list<int>|null $daysOfMonth
 */
final class EveryXMonthsFrequencyConfig extends FrequencyConfig
{
    /** @var int<1, 12> */
    private int $frequencyMonths;

    /**
     * @param  int<1, 12>  $frequencyMonths
     */
    public function __construct(
        int $frequencyMonths,
        public ?array $days_of_month = null,
        public ?int $start_month = null,
    ) {
        $this->frequencyMonths = $frequencyMonths;
    }

    public static function fromArray(array $data): self
    {
        if (! array_key_exists('frequencyMonths', $data)) {
            throw new \InvalidArgumentException("Missing 'frequencyMonths' key in EveryXMonthsFrequencyConfig data array.");
        }

        if (array_key_exists('day_of_month', $data) && ! array_key_exists('days_of_month', $data)) {
            $data['days_of_month'] = [$data['day_of_month']];
            unset($data['day_of_month']);
        }

        return new self(
            frequencyMonths: (int) $data['frequencyMonths'],
            days_of_month: $data['days_of_month'] ?? null,
            start_month: $data['start_month'] ?? null,
        );
    }

    /**
     * @return int<1, 12>
     */
    public function getFrequencyMonths(): int
    {
        return $this->frequencyMonths;
    }

    public function setStartFromStartDate(CarbonInterface $startDate): self
    {
        if ($this->start_month === null) {
            $this->start_month = $startDate->month;
        }

        return $this;
    }

    public function getNextRecurrence(CarbonInterface $current): CarbonInterface
    {
        $daysOfMonth = $this->days_of_month ?? [$current->day];
        if ($current->day >= max($daysOfMonth)) {
            $dayOfMonth = min($daysOfMonth);

            return $current->copy()->addMonths($this->frequencyMonths)->day($dayOfMonth);
        }
        $dayOfMonth = min(array_filter($daysOfMonth, fn ($day) => $day > $current->day));

        return $current->copy()->day($dayOfMonth);
    }

    public function shouldCreateInstance(CarbonInterface $date): bool
    {
        $daysOfMonth = $this->days_of_month ?? [$date->day];
        $monthDiff = ($date->month - $this->start_month + 12) % $this->frequencyMonths;

        return in_array($date->day, $daysOfMonth) && $monthDiff === 0;
    }

    public function shouldCreateRecurringInstance(Schedule $schedule, CarbonInterface $date): bool
    {
        $daysOfMonth = $this->days_of_month ?? [$schedule->start_date->day];
        $monthDiff = ($date->month - $this->start_month + 12) % $this->frequencyMonths;

        return in_array($date->day, $daysOfMonth) && $monthDiff === 0;
    }

    public function toArray(): array
    {
        return [
            'days_of_month' => $this->days_of_month,
            'start_month' => $this->start_month,
            'frequencyMonths' => $this->frequencyMonths,
        ];
    }
}
