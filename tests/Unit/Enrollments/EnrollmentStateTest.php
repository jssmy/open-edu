<?php

use App\Modules\Enrollments\Domain\Enrollment;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Shared\Exceptions\DomainException;

it('allows the defined active and suspended enrollment transitions', function () {
    foreach ([
        [EnrollmentStatus::Active, EnrollmentStatus::Suspended],
        [EnrollmentStatus::Suspended, EnrollmentStatus::Active],
        [EnrollmentStatus::Active, EnrollmentStatus::Cancelled],
        [EnrollmentStatus::Suspended, EnrollmentStatus::Cancelled],
    ] as [$initial, $next]) {
        $enrollment = new Enrollment(1, 1, 1, $initial, new DateTimeImmutable);

        expect($enrollment->changeStatus($next)->status)->toBe($next);
    }
});

it('does not allow a cancelled enrollment to be reactivated', function () {
    $enrollment = new Enrollment(
        1,
        1,
        1,
        EnrollmentStatus::Cancelled,
        new DateTimeImmutable,
    );

    expect(fn () => $enrollment->changeStatus(EnrollmentStatus::Active))
        ->toThrow(DomainException::class);
});
