<?php

use Zap\Facades\Zap;
use Zap\Helper\TimeHelper;
use Zap\Models\SchedulePeriod;

// ─── TimeHelper Unit Tests ───────────────────────────────────────────

it('detects overnight periods correctly', function () {
    expect(TimeHelper::isOvernight('22:00', '02:00'))->toBeTrue();
    expect(TimeHelper::isOvernight('23:30', '01:00'))->toBeTrue();
    expect(TimeHelper::isOvernight('09:00', '17:00'))->toBeFalse();
    expect(TimeHelper::isOvernight('00:00', '08:00'))->toBeFalse();
});

it('calculates overnight duration correctly', function () {
    // Normal period
    expect(TimeHelper::durationInMinutes('09:00', '17:00'))->toBe(480); // 8 hours

    // Overnight periods
    expect(TimeHelper::durationInMinutes('22:00', '02:00'))->toBe(240); // 4 hours
    expect(TimeHelper::durationInMinutes('23:00', '01:00'))->toBe(120); // 2 hours
    expect(TimeHelper::durationInMinutes('20:00', '04:00'))->toBe(480); // 8 hours
});

it('detects overlap between overnight periods', function () {
    // Overnight period overlapping with normal period
    expect(TimeHelper::periodsOverlap('22:00', '02:00', '23:00', '23:30'))->toBeTrue();
    expect(TimeHelper::periodsOverlap('22:00', '02:00', '01:00', '01:30'))->toBeTrue();

    // Two overnight periods overlapping
    expect(TimeHelper::periodsOverlap('22:00', '03:00', '23:00', '04:00'))->toBeTrue();

    // Overnight period NOT overlapping with early morning
    expect(TimeHelper::periodsOverlap('22:00', '02:00', '03:00', '05:00'))->toBeFalse();

    // Overnight period NOT overlapping with daytime
    expect(TimeHelper::periodsOverlap('22:00', '02:00', '10:00', '14:00'))->toBeFalse();

    // Normal periods still work
    expect(TimeHelper::periodsOverlap('09:00', '12:00', '11:00', '14:00'))->toBeTrue();
    expect(TimeHelper::periodsOverlap('09:00', '12:00', '13:00', '14:00'))->toBeFalse();
});

it('detects overlap with buffer for overnight periods', function () {
    // Without buffer: no overlap
    expect(TimeHelper::periodsOverlapWithBuffer('22:00', '23:00', '23:00', '00:30', 0))->toBeFalse();

    // With 15 min buffer: overlap
    expect(TimeHelper::periodsOverlapWithBuffer('22:00', '23:00', '23:00', '00:30', 15))->toBeTrue();

    // Overnight with buffer
    expect(TimeHelper::periodsOverlapWithBuffer('22:00', '02:00', '02:00', '03:00', 0))->toBeFalse();
    expect(TimeHelper::periodsOverlapWithBuffer('22:00', '02:00', '02:00', '03:00', 30))->toBeTrue();
});

// ─── SchedulePeriod Model Tests ──────────────────────────────────────

it('calculates duration for overnight schedule period', function () {
    $user = createUser();

    $schedule = Zap::for($user)
        ->named('Night Shift')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->save();

    $period = $schedule->periods->first();

    expect($period->duration_minutes)->toBe(240); // 4 hours, not 20 hours
});

it('reports isOvernight correctly on schedule period', function () {
    $user = createUser();

    $schedule = Zap::for($user)
        ->named('Night Shift')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->addPeriod('09:00', '12:00')
        ->save();

    $overnightPeriod = $schedule->periods->where('start_time', '22:00')->first();
    $normalPeriod = $schedule->periods->where('start_time', '09:00')->first();

    expect($overnightPeriod->isOvernight())->toBeTrue();
    expect($normalPeriod->isOvernight())->toBeFalse();
});

it('calculates end_date_time correctly for overnight periods', function () {
    $user = createUser();

    $schedule = Zap::for($user)
        ->named('Night Shift')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->save();

    $period = $schedule->periods->first();

    // end_date_time should be next day
    expect($period->end_date_time->format('Y-m-d'))->toBe('2025-01-11');
    expect($period->end_date_time->format('H:i'))->toBe('02:00');
    expect($period->start_date_time->format('Y-m-d'))->toBe('2025-01-10');
});

// ─── Overnight Period Overlap Detection ──────────────────────────────

it('detects overlap between overnight schedule periods', function () {
    $user = createUser();

    $schedule = Zap::for($user)
        ->named('Night Shift')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->addPeriod('23:00', '01:00')
        ->save();

    $period1 = $schedule->periods->where('start_time', '22:00')->first();
    $period2 = $schedule->periods->where('start_time', '23:00')->first();

    expect($period1->overlapsWith($period2))->toBeTrue();
});

// ─── Bookable Slots with Overnight Availability ──────────────────────

it('generates bookable slots for overnight availability', function () {
    $user = createUser();

    Zap::for($user)
        ->named('Night Club Hours')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->save();

    $slots = $user->getBookableSlots('2025-01-10', 60);

    expect($slots)->not()->toBeEmpty();

    $startTimes = collect($slots)->pluck('start_time')->toArray();

    // Should have slots at 22:00, 23:00, 00:00, 01:00
    expect($startTimes)->toContain('22:00');
    expect($startTimes)->toContain('23:00');
    expect($startTimes)->toContain('00:00');
    expect($startTimes)->toContain('01:00');
    expect(count($startTimes))->toBe(4);
});

it('generates bookable slots for overnight availability with 30min intervals', function () {
    $user = createUser();

    Zap::for($user)
        ->named('Night Club Hours')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '01:00')
        ->save();

    $slots = $user->getBookableSlots('2025-01-10', 30);

    $startTimes = collect($slots)->pluck('start_time')->toArray();

    // 22:00, 22:30, 23:00, 23:30, 00:00, 00:30
    expect($startTimes)->toContain('22:00');
    expect($startTimes)->toContain('22:30');
    expect($startTimes)->toContain('23:00');
    expect($startTimes)->toContain('23:30');
    expect($startTimes)->toContain('00:00');
    expect($startTimes)->toContain('00:30');
    expect(count($startTimes))->toBe(6);
});

// ─── Overnight Appointment Blocking ─────────────────────────────────

it('blocks overnight slots when appointment exists', function () {
    $user = createUser();

    // Availability: 22:00-02:00
    Zap::for($user)
        ->named('Night Club Hours')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('22:00', '02:00')
        ->save();

    // Appointment: 23:00-00:00
    Zap::for($user)
        ->named('Late Booking')
        ->appointment()
        ->from('2025-01-10')
        ->addPeriod('23:00', '00:00')
        ->save();

    $slots = $user->getBookableSlots('2025-01-10', 60);

    $slot23 = collect($slots)->firstWhere('start_time', '23:00');

    expect($slot23)->not()->toBeNull();
    expect($slot23['is_available'])->toBeFalse();
});

// ─── Conflict Detection with Overnight Periods ──────────────────────

it('detects conflict between two overnight appointments', function () {
    $user = createUser();

    // First overnight appointment: 23:00→01:00
    Zap::for($user)
        ->named('Late Appointment 1')
        ->appointment()
        ->from('2025-01-10')
        ->addPeriod('23:00', '01:00')
        ->save();

    // Second overlapping appointment: 00:00→02:00 — should throw conflict exception
    expect(fn () => Zap::for($user)
        ->named('Late Appointment 2')
        ->appointment()
        ->from('2025-01-10')
        ->addPeriod('00:00', '02:00')
        ->save()
    )->toThrow(\Zap\Exceptions\ScheduleConflictException::class);
});

it('does not detect conflict between non-overlapping overnight appointments', function () {
    $user = createUser();

    // First overnight appointment
    Zap::for($user)
        ->named('Late Appointment 1')
        ->appointment()
        ->from('2025-01-10')
        ->addPeriod('22:00', '23:00')
        ->save();

    // Second appointment after midnight — no overlap
    $schedule2 = Zap::for($user)
        ->named('Late Appointment 2')
        ->appointment()
        ->from('2025-01-10')
        ->addPeriod('01:00', '02:00')
        ->save();

    expect($user->hasScheduleConflict($schedule2))->toBeFalse();
});

// ─── Normal Periods Regression ───────────────────────────────────────

it('normal periods still work correctly (regression)', function () {
    $user = createUser();

    Zap::for($user)
        ->named('Office Hours')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('09:00', '17:00')
        ->save();

    $slots = $user->getBookableSlots('2025-01-10', 60);

    expect($slots)->not()->toBeEmpty();

    foreach ($slots as $slot) {
        expect($slot['start_time'])->toBeGreaterThanOrEqual('09:00');
        expect($slot['end_time'])->toBeLessThanOrEqual('17:00');
    }

    $startTimes = collect($slots)->pluck('start_time')->toArray();
    expect($startTimes)->toContain('09:00');
    expect($startTimes)->toContain('16:00');
    expect(count($startTimes))->toBe(8); // 09:00-16:00 = 8 slots
});

it('normal duration calculation still works (regression)', function () {
    $user = createUser();

    $schedule = Zap::for($user)
        ->named('Office')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('09:00', '17:00')
        ->save();

    $period = $schedule->periods->first();

    expect($period->duration_minutes)->toBe(480); // 8 hours
    expect($period->isOvernight())->toBeFalse();
});

// ─── Weekly Recurring with Overnight ─────────────────────────────────

it('generates overnight slots for weekly recurring schedule', function () {
    $user = createUser();

    // Friday night shift 22:00-03:00
    Zap::for($user)
        ->named('Friday Night')
        ->availability()
        ->weekDays(['friday'], '22:00', '03:00')
        ->forYear(2025)
        ->save();

    // 2025-01-10 is a Friday
    $slots = $user->getBookableSlots('2025-01-10', 60);

    expect($slots)->not()->toBeEmpty();

    $startTimes = collect($slots)->pluck('start_time')->toArray();

    expect($startTimes)->toContain('22:00');
    expect($startTimes)->toContain('23:00');
    expect($startTimes)->toContain('00:00');
    expect($startTimes)->toContain('01:00');
    expect($startTimes)->toContain('02:00');
});

// ─── Mixed Normal + Overnight ────────────────────────────────────────

it('handles mixed normal and overnight availability periods', function () {
    $user = createUser();

    Zap::for($user)
        ->named('Full Day')
        ->availability()
        ->from('2025-01-10')
        ->addPeriod('09:00', '12:00')  // Morning
        ->addPeriod('22:00', '02:00')  // Night
        ->save();

    $slots = $user->getBookableSlots('2025-01-10', 60);

    $startTimes = collect($slots)->pluck('start_time')->toArray();

    // Morning slots
    expect($startTimes)->toContain('09:00');
    expect($startTimes)->toContain('10:00');
    expect($startTimes)->toContain('11:00');

    // Night slots
    expect($startTimes)->toContain('22:00');
    expect($startTimes)->toContain('23:00');
    expect($startTimes)->toContain('00:00');
    expect($startTimes)->toContain('01:00');
});
