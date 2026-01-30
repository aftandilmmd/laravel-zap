<?php

namespace Zap\Data\WeeklyFrequencyConfig;

use Carbon\CarbonInterface;

/**
 * @property-read list<string> $daysOfWeek
 */
final class WeeklyFrequencyConfig extends AbstractWeeklyFrequencyConfig
{
    public function __construct(
        public array $days = [],
        CarbonInterface|string|null $startsOn = null,
    ) {
        parent::__construct($days, $startsOn);
    }

    public static function getFrequency(): int
    {
        return 1;
    }
}
