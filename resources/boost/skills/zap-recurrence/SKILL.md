# Laravel Zap - Recurrence Patterns

This skill covers all recurrence patterns available in Laravel Zap for creating recurring schedules.

## Available Frequencies

| Frequency | Description |
|-----------|-------------|
| `daily` | Every day |
| `weekly` | Specific days each week |
| `weekly_odd` | Specific days on odd-numbered weeks |
| `weekly_even` | Specific days on even-numbered weeks |
| `biweekly` | Every two weeks |
| `monthly` | Specific days each month |
| `bimonthly` | Every two months |
| `quarterly` | Every three months |
| `semiannually` | Every six months |
| `annually` | Once per year |

## Daily Recurrence

```php
Zap::for($resource)
    ->named('Daily Schedule')
    ->availability()
    ->daily()
    ->from('2025-01-01')
    ->to('2025-12-31')
    ->addPeriod('09:00', '17:00')
    ->save();
```

## Weekly Recurrence

### Standard Weekly

```php
// Weekly on specific days
Zap::for($doctor)
    ->named('Office Hours')
    ->availability()
    ->weekly(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
    ->forYear(2025)
    ->addPeriod('09:00', '17:00')
    ->save();

// Convenience method: weekDays with time period
Zap::for($doctor)
    ->named('Office Hours')
    ->availability()
    ->weekDays(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '09:00', '17:00')
    ->forYear(2025)
    ->save();
```

### Odd Weeks Only

Runs only on odd-numbered weeks of the year (weeks 1, 3, 5, 7, etc.):

```php
Zap::for($employee)
    ->named('Team A Schedule')
    ->availability()
    ->weeklyOdd(['monday', 'wednesday', 'friday'])
    ->forYear(2025)
    ->addPeriod('09:00', '17:00')
    ->save();

// With convenience method
Zap::for($employee)
    ->named('Team A Schedule')
    ->availability()
    ->weekOddDays(['monday', 'wednesday', 'friday'], '09:00', '17:00')
    ->forYear(2025)
    ->save();
```

### Even Weeks Only

Runs only on even-numbered weeks of the year (weeks 2, 4, 6, 8, etc.):

```php
Zap::for($employee)
    ->named('Team B Schedule')
    ->availability()
    ->weeklyEven(['monday', 'wednesday', 'friday'])
    ->forYear(2025)
    ->addPeriod('09:00', '17:00')
    ->save();

// With convenience method
Zap::for($employee)
    ->named('Team B Schedule')
    ->availability()
    ->weekEvenDays(['monday', 'wednesday', 'friday'], '09:00', '17:00')
    ->forYear(2025)
    ->save();
```

## Bi-Weekly Recurrence

Every two weeks, anchored to the week of the start date:

```php
Zap::for($resource)
    ->named('Bi-Weekly Meeting')
    ->appointment()
    ->biweekly(['tuesday', 'thursday'])
    ->from('2025-01-07')
    ->to('2025-03-31')
    ->addPeriod('10:00', '11:00')
    ->save();

// With custom anchor date
Zap::for($resource)
    ->named('Bi-Weekly Meeting')
    ->appointment()
    ->biweekly(['tuesday', 'thursday'], '2025-01-14') // startsOn anchor
    ->from('2025-01-07')
    ->to('2025-03-31')
    ->addPeriod('10:00', '11:00')
    ->save();
```

## Monthly Recurrence

Specific days of the month:

```php
// Run on the 1st and 15th of each month
Zap::for($resource)
    ->named('Monthly Review')
    ->appointment()
    ->monthly(['days_of_month' => [1, 15]])
    ->forYear(2025)
    ->addPeriod('14:00', '15:00')
    ->save();
```

## Bi-Monthly Recurrence

Every two months:

```php
// Run on 5th and 20th, every two months starting from February
Zap::for($resource)
    ->named('Bi-Monthly Report')
    ->appointment()
    ->bimonthly([
        'days_of_month' => [5, 20],
        'start_month' => 2 // Anchor to February
    ])
    ->from('2025-01-05')
    ->to('2025-06-30')
    ->addPeriod('10:00', '11:00')
    ->save();
```

## Quarterly Recurrence

Every three months:

```php
// Run on 7th and 21st, every quarter starting from February
Zap::for($resource)
    ->named('Quarterly Review')
    ->appointment()
    ->quarterly([
        'days_of_month' => [7, 21],
        'start_month' => 2
    ])
    ->from('2025-02-15')
    ->to('2025-11-15')
    ->addPeriod('09:00', '12:00')
    ->save();
```

## Semi-Annual Recurrence

Every six months:

```php
// Run on the 10th, every six months starting from March
Zap::for($resource)
    ->named('Semi-Annual Audit')
    ->appointment()
    ->semiannually([
        'days_of_month' => [10],
        'start_month' => 3
    ])
    ->from('2025-03-10')
    ->to('2025-12-10')
    ->addPeriod('09:00', '17:00')
    ->save();
```

## Annual Recurrence

Once per year:

```php
// Run on 1st and 15th of April each year
Zap::for($resource)
    ->named('Annual Conference')
    ->blocked()
    ->annually([
        'days_of_month' => [1, 15],
        'start_month' => 4
    ])
    ->from('2025-04-01')
    ->to('2026-04-01')
    ->addPeriod('09:00', '18:00')
    ->save();
```

## Custom Recurring Frequency

Use the `recurring()` method for full control:

```php
use Zap\Enums\Frequency;

Zap::for($resource)
    ->named('Custom Recurring')
    ->availability()
    ->recurring(Frequency::MONTHLY, ['days_of_month' => [1, 15]])
    ->forYear(2025)
    ->addPeriod('10:00', '12:00')
    ->save();

// Using string frequency
Zap::for($resource)
    ->named('Custom Recurring')
    ->availability()
    ->recurring('weekly', ['days' => ['monday', 'friday']])
    ->forYear(2025)
    ->addPeriod('10:00', '12:00')
    ->save();
```

## Use Cases

### Alternating Teams Schedule

```php
// Team A works odd weeks
Zap::for($teamALocation)
    ->named('Team A')
    ->availability()
    ->weekOddDays(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '09:00', '17:00')
    ->forYear(2025)
    ->save();

// Team B works even weeks
Zap::for($teamBLocation)
    ->named('Team B')
    ->availability()
    ->weekEvenDays(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '09:00', '17:00')
    ->forYear(2025)
    ->save();
```

### Employee Shift Rotation

```php
// Morning shift - daily
Zap::for($employee)
    ->named('Morning Shift')
    ->availability()
    ->daily()
    ->from('2025-01-01')
    ->to('2025-06-30')
    ->addPeriod('06:00', '14:00')
    ->save();

// Vacation block
Zap::for($employee)
    ->named('Vacation')
    ->blocked()
    ->between('2025-06-01', '2025-06-15')
    ->addPeriod('00:00', '23:59')
    ->save();
```

### Monthly Review Meetings

```php
// First Monday of each month (approximate with day 1-7)
Zap::for($room)
    ->named('Monthly Review')
    ->appointment()
    ->monthly(['days_of_month' => [1]])
    ->forYear(2025)
    ->addPeriod('09:00', '10:00')
    ->withMetadata(['meeting_type' => 'review'])
    ->save();
```

## Querying Recurring Schedules

```php
// Get all recurring schedules
$recurring = $model->recurringSchedules()->get();

// Filter by frequency using query builder
use Zap\Models\Schedule;
use Zap\Enums\Frequency;

$weekly = Schedule::where('frequency', Frequency::WEEKLY)->get();
```
