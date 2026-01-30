<?php

namespace Zap\Data\MonthlyFrequencyConfig;

/**
 * @property-read list<int>|null $daysOfMonth
 */
final class MonthlyFrequencyConfig extends AbstractMonthlyFrequencyConfig
{
    public function __construct(
        public ?array $days_of_month,
        public ?int $start_month = null
    ) {
        parent::__construct($days_of_month, $start_month);
    }

    protected static function getFrequency(): int
    {
        return 1;
    }
}
