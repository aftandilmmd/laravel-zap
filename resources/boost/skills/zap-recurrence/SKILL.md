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
| `everyThreeWeeks` | Every three weeks |
| `everyFourWeeks` | Every four weeks |
| `everyFiveWeeks` ... `everyFiftyTwoWeeks` | Every N weeks (3-52) |
| `everyFourMonths` | Every four months |
| `everyFiveMonths` ... `everyElevenMonths` | Every N months (4, 5, 7-11) |
| `monthly_ordinal_weekday` | First, second, third, fourth, or last weekday of each month (e.g. `firstWednesdayOfMonth`, `lastMondayOfMonth`) |

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

## Monthly Ordinal Weekday (first / second / third / fourth / last X of month)

Recur on the 1st, 2nd, 3rd, 4th, or **last** occurrence of a given weekday each month. Use method names: `first{Day}OfMonth`, `second{Day}OfMonth`, `third{Day}OfMonth`, `fourth{Day}OfMonth`, `last{Day}OfMonth` with any day name (Sunday through Saturday).

```php
// Every 1st Wednesday of the month
Zap::for($resource)
    ->named('Monthly Standup')
    ->appointment()
    ->firstWednesdayOfMonth()
    ->forYear(2025)
    ->addPeriod('09:00', '10:00')
    ->save();

// Every 2nd Friday of the month
Zap::for($resource)
    ->named('Bi-Monthly Review')
    ->appointment()
    ->secondFridayOfMonth()
    ->forYear(2025)
    ->addPeriod('14:00', '15:00')
    ->save();

// Every last Monday of the month
Zap::for($resource)
    ->named('Month-End Retro')
    ->appointment()
    ->lastMondayOfMonth()
    ->forYear(2025)
    ->addPeriod('16:00', '17:00')
    ->save();
```

Available methods (replace `{Day}` with Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday): `first{Day}OfMonth`, `second{Day}OfMonth`, `third{Day}OfMonth`, `fourth{Day}OfMonth`, `last{Day}OfMonth`. Examples: `firstWednesdayOfMonth`, `secondFridayOfMonth`, `lastMondayOfMonth`.

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

## Dynamic Weekly Frequencies (3-52 weeks)

For frequencies between 3 and 52 weeks, use the dynamic `everyXWeeks` methods:

```php
// Every three weeks on Monday and Friday
Zap::for($resource)
    ->named('Tri-Weekly Sync')
    ->appointment()
    ->everyThreeWeeks(['monday', 'friday'])
    ->from('2025-01-06')
    ->to('2025-12-31')
    ->addPeriod('10:00', '11:00')
    ->save();

// Every four weeks with custom anchor date
Zap::for($resource)
    ->named('Monthly-ish Meeting')
    ->appointment()
    ->everyFourWeeks(['tuesday'], '2025-01-06') // startsOn anchor
    ->from('2025-01-13')
    ->to('2025-12-31')
    ->addPeriod('14:00', '15:00')
    ->save();

// Every six weeks
Zap::for($resource)
    ->named('Six-Week Review')
    ->appointment()
    ->everySixWeeks(['wednesday'])
    ->forYear(2025)
    ->addPeriod('09:00', '10:00')
    ->save();

// Compound numbers work too
Zap::for($resource)
    ->named('21-Week Cycle')
    ->appointment()
    ->everyTwentyOneWeeks(['monday'])
    ->forYear(2025)
    ->addPeriod('10:00', '11:00')
    ->save();
```

Available methods: `everyThreeWeeks`, `everyFourWeeks`, `everyFiveWeeks`, ... through `everyFiftyTwoWeeks`.

## Dynamic Monthly Frequencies (4, 5, 7-11 months)

For month frequencies not covered by existing methods (4, 5, 7, 8, 9, 10, 11 months), use the dynamic `everyXMonths` methods:

```php
// Every four months on the 15th
Zap::for($resource)
    ->named('Quadrimester Review')
    ->appointment()
    ->everyFourMonths(['day_of_month' => 15])
    ->forYear(2025)
    ->addPeriod('09:00', '12:00')
    ->save();

// Every five months with multiple days and start_month anchor
Zap::for($resource)
    ->named('Five-Month Cycle')
    ->appointment()
    ->everyFiveMonths([
        'days_of_month' => [1, 15],
        'start_month' => 2
    ])
    ->forYear(2025)
    ->addPeriod('10:00', '11:00')
    ->save();

// Every seven months
Zap::for($resource)
    ->named('Seven-Month Audit')
    ->appointment()
    ->everySevenMonths(['days_of_month' => [10]])
    ->forYear(2025)
    ->addPeriod('14:00', '16:00')
    ->save();
```

Available methods: `everyFourMonths`, `everyFiveMonths`, `everySevenMonths`, `everyEightMonths`, `everyNineMonths`, `everyTenMonths`, `everyElevenMonths`.

Note: `everyOneMonth`, `everyTwoMonths`, `everyThreeMonths`, `everySixMonths`, and `everyTwelveMonths` redirect to the existing `monthly()`, `bimonthly()`, `quarterly()`, `semiannually()`, and `annually()` methods respectively.

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
// First Monday of each month (ordinal weekday)
Zap::for($room)
    ->named('Monthly Review')
    ->appointment()
    ->firstMondayOfMonth()
    ->forYear(2025)
    ->addPeriod('09:00', '10:00')
    ->withMetadata(['meeting_type' => 'review'])
    ->save();

// Last Friday of each month (month-end review)
Zap::for($room)
    ->named('Month-End Review')
    ->appointment()
    ->lastFridayOfMonth()
    ->forYear(2025)
    ->addPeriod('14:00', '15:00')
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
