<?php

use App\Modules\Scheduling\Domain\ConflictDetector;
use App\Modules\Scheduling\Domain\CourseSession;
use App\Modules\Scheduling\Domain\OfferingStatus;
use App\Modules\Scheduling\Domain\SchedulingRuleViolation;
use App\Modules\Scheduling\Domain\SessionStatus;

it('detects overlapping sessions on the same date', function () {
    $detector = new ConflictDetector;
    $existing = [CourseSession::fromPrimitives('Sesión 1', '2026-11-10', '08:00', '10:00', 1)];
    $proposed = [CourseSession::fromPrimitives('Sesión 2', '2026-11-10', '09:00', '11:00', 1)];

    expect($detector->hasConflict($proposed, $existing))->toBeTrue();
});

it('allows consecutive sessions on the same date without conflict', function () {
    $detector = new ConflictDetector;
    $existing = [CourseSession::fromPrimitives('Sesión 1', '2026-11-10', '08:00', '10:00', 1)];
    $proposed = [CourseSession::fromPrimitives('Sesión 2', '2026-11-10', '10:00', '12:00', 1)];

    expect($detector->hasConflict($proposed, $existing))->toBeFalse();
});

it('allows sessions at the same time on different dates', function () {
    $detector = new ConflictDetector;
    $existing = [CourseSession::fromPrimitives('Sesión 1', '2026-11-10', '08:00', '10:00', 1)];
    $proposed = [CourseSession::fromPrimitives('Sesión 2', '2026-11-11', '08:00', '10:00', 1)];

    expect($detector->hasConflict($proposed, $existing))->toBeFalse();
});

it('ignores cancelled sessions when checking conflicts', function () {
    $detector = new ConflictDetector;
    $existing = [CourseSession::fromPrimitives('Sesión Cancelada', '2026-11-10', '08:00', '10:00', 1, null, null, SessionStatus::Cancelled)];
    $proposed = [CourseSession::fromPrimitives('Sesión Nueva', '2026-11-10', '08:00', '10:00', 1)];

    expect($detector->hasConflict($proposed, $existing))->toBeFalse();
});

it('transitions offering and session statuses automatically based on start and end dates/times', function () {
    expect(OfferingStatus::Published->resolveForDates('2026-11-01', '2026-12-01', '2026-10-15'))->toBe(OfferingStatus::Published)
        ->and(OfferingStatus::Published->resolveForDates('2026-11-01', '2026-12-01', '2026-11-10'))->toBe(OfferingStatus::InProgress)
        ->and(OfferingStatus::Published->resolveForDates('2026-11-01', '2026-12-01', '2026-12-02'))->toBe(OfferingStatus::Finished)
        ->and(OfferingStatus::Draft->resolveForDates('2026-11-01', '2026-12-01', '2026-11-10'))->toBe(OfferingStatus::Draft)
        ->and(OfferingStatus::Cancelled->resolveForDates('2026-11-01', '2026-12-01', '2026-11-10'))->toBe(OfferingStatus::Cancelled);

    $nowInProgress = new DateTimeImmutable('2026-11-10 09:00:00');
    $nowFinished = new DateTimeImmutable('2026-11-10 10:30:00');

    expect(SessionStatus::Scheduled->resolveForDateTime('2026-11-10', '08:00', '10:00', $nowInProgress))->toBe(SessionStatus::InProgress)
        ->and(SessionStatus::Scheduled->resolveForDateTime('2026-11-10', '08:00', '10:00', $nowFinished))->toBe(SessionStatus::Finished)
        ->and(SessionStatus::Cancelled->resolveForDateTime('2026-11-10', '08:00', '10:00', $nowInProgress))->toBe(SessionStatus::Cancelled);
});

it('rejects a session where end time is not after start time', function () {
    CourseSession::fromPrimitives('Sesión Inválida', '2026-11-10', '10:00', '10:00', 1);
})->throws(SchedulingRuleViolation::class);
