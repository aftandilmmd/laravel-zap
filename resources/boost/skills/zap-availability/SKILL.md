# Laravel Zap - Availability & Bookable Slots

This skill covers checking availability, retrieving bookable slots, and querying schedules in Laravel Zap.

## Checking Bookability

### Check if Bookable on a Date

```php
// Check if there is at least one bookable slot on the day
// Parameters: date, slot duration in minutes
$isBookable = $doctor->isBookableAt('2025-01-15', 60);

// With buffer time (minutes between slots)
$isBookable = $doctor->isBookableAt('2025-01-15', 60, 15);
```

### Check Specific Time Range

```php
// Check if a specific time range is bookable
$isBookable = $doctor->isBookableAtTime('2025-01-15', '09:00', '09:30');
```

## Getting Bookable Slots

```php
// Get all bookable slots for a date
// Parameters: date, slot duration (minutes), buffer (minutes)
$slots = $doctor->getBookableSlots('2025-01-15', 60, 15);

// Returns array of slots:
// [
//     ['start_time' => '09:00', 'end_time' => '10:00', 'is_available' => true, 'buffer_minutes' => 15],
//     ['start_time' => '10:15', 'end_time' => '11:15', 'is_available' => false, 'buffer_minutes' => 15],
//     ...
// ]
```

Bookable slots are generated based on:
1. Availability schedules (defines when the resource CAN be booked)
2. Appointment, blocked, and custom schedules (defines when slots are NOT available)

## Finding Next Available Slot

```php
// Find the next available slot starting from a date
$nextSlot = $doctor->getNextBookableSlot('2025-01-15', 60, 15);

// Returns: ['start_time' => '09:00', 'end_time' => '10:00', 'is_available' => true, 'date' => '2025-01-15', 'buffer_minutes' => 15]

// Start from today
$nextSlot = $doctor->getNextBookableSlot(null, 60);
```

The method searches up to 365 days in the future to cover all recurring frequencies.

## Querying Schedules

### By Date

```php
// Get schedules for a specific date
$schedules = $doctor->schedulesForDate('2025-01-15')->get();

// Get schedules within a date range
$schedules = $doctor->schedulesForDateRange('2025-01-01', '2025-01-31')->get();
```

### By Type

```php
// Get only appointment schedules
$appointments = $doctor->appointmentSchedules()->get();

// Get only availability schedules
$availabilities = $doctor->availabilitySchedules()->get();

// Get only blocked schedules
$blocked = $doctor->blockedSchedules()->get();

// Get schedules of a specific type
$schedules = $doctor->schedulesOfType('appointment')->get();
```

### By State

```php
// Get only active schedules
$active = $doctor->activeSchedules()->get();

// Get recurring schedules
$recurring = $doctor->recurringSchedules()->get();

// Get all schedules
$all = $doctor->schedules()->get();
```

### Query Builder Scopes

The Schedule model provides these query scopes:

```php
use Zap\Models\Schedule;

Schedule::active()->get();                          // Only active schedules
Schedule::recurring()->get();                       // Only recurring schedules
Schedule::ofType(ScheduleTypes::APPOINTMENT)->get(); // By type
Schedule::availability()->get();                    // Availability schedules
Schedule::appointments()->get();                    // Appointment schedules
Schedule::blocked()->get();                         // Blocked schedules
Schedule::forDate('2025-01-15')->get();             // For specific date
Schedule::forDateRange('2025-01-01', '2025-01-31')->get(); // For date range
```

## Total Scheduled Time

```php
// Get total scheduled time in minutes for a date range
$totalMinutes = $doctor->getTotalScheduledTime('2025-01-01', '2025-12-31');
```

## Checking for Schedules

```php
// Check if the model has any schedules
$hasSchedules = $doctor->hasSchedules();

// Check if the model has any active schedules
$hasActive = $doctor->hasActiveSchedules();
```

## Real-World Example: Doctor Appointment System

```php
use Zap\Facades\Zap;

// 1. Set up doctor's office hours
Zap::for($doctor)
    ->named('Office Hours')
    ->availability()
    ->forYear(2025)
    ->weekly(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
    ->addPeriod('09:00', '12:00')
    ->addPeriod('14:00', '17:00')
    ->save();

// 2. Block lunch time
Zap::for($doctor)
    ->named('Lunch Break')
    ->blocked()
    ->forYear(2025)
    ->weekly(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
    ->addPeriod('12:00', '13:00')
    ->save();

// 3. Get available slots for booking
$slots = $doctor->getBookableSlots('2025-01-15', 60, 15);

// 4. Check if specific time is available before booking
if ($doctor->isBookableAtTime('2025-01-15', '10:00', '11:00')) {
    // Book the appointment
    Zap::for($doctor)
        ->named('Patient Consultation')
        ->appointment()
        ->from('2025-01-15')
        ->addPeriod('10:00', '11:00')
        ->withMetadata(['patient_id' => $patientId])
        ->save();
}

// 5. Find next available slot for a patient
$nextSlot = $doctor->getNextBookableSlot(now()->format('Y-m-d'), 60);
```

## Real-World Example: Meeting Room Booking

```php
// Room availability
Zap::for($room)
    ->named('Conference Room A')
    ->availability()
    ->weekDays(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '08:00', '18:00')
    ->forYear(2025)
    ->save();

// Get available slots
$slots = $room->getBookableSlots('2025-03-15', 60);

// Book meeting
Zap::for($room)
    ->named('Board Meeting')
    ->appointment()
    ->from('2025-03-15')
    ->addPeriod('09:00', '11:00')
    ->withMetadata(['organizer' => 'john@company.com'])
    ->save();
```

## Buffer Time Configuration

Buffer time adds spacing between slots:

```php
// In config/zap.php
'time_slots' => [
    'buffer_minutes' => 15, // Default buffer between slots
],

// Or per-call
$slots = $doctor->getBookableSlots('2025-01-15', 60, 15); // 15 min buffer
```

With a 15-minute buffer and 60-minute slots:
- Slot 1: 09:00 - 10:00
- Slot 2: 10:15 - 11:15 (15 min buffer after slot 1)
- Slot 3: 11:30 - 12:30
