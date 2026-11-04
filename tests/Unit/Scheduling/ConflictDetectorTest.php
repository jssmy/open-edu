<?php

use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;
use App\Modules\Scheduling\Domain\TimeBlock;

it('detects overlapping blocks on the same day', function () {
    $detector = new ConflictDetector;
    $existing = [TimeBlock::fromTimes(1, '08:00', '10:00')];
    $proposed = [TimeBlock::fromTimes(1, '09:00', '11:00')];

    expect($detector->hasConflict($proposed, $existing))->toBeTrue();
});

it('allows consecutive blocks without conflict', function () {
    $detector = new ConflictDetector;
    $existing = [TimeBlock::fromTimes(1, '08:00', '10:00')];
    $proposed = [TimeBlock::fromTimes(1, '10:00', '12:00')];

    expect($detector->hasConflict($proposed, $existing))->toBeFalse();
});

it('allows blocks at the same time on different days', function () {
    $detector = new ConflictDetector;
    $existing = [TimeBlock::fromTimes(1, '08:00', '10:00')];
    $proposed = [TimeBlock::fromTimes(2, '08:00', '10:00')];

    expect($detector->hasConflict($proposed, $existing))->toBeFalse();
});

it('rejects a time block where end is not after start', function () {
    TimeBlock::fromTimes(1, '10:00', '10:00');
})->throws(SchedulingRuleViolation::class);

