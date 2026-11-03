<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\Gate;

it('allows access only to active students with an active enrollment in a published course', function () {
    $user = userWithRole(Role::Estudiante);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    expect(Gate::forUser($user)->allows('accessCourse', $course))->toBeTrue();

    $enrollment->update(['status' => EnrollmentStatus::Suspended]);
    expect(Gate::forUser($user)->allows('accessCourse', $course))->toBeFalse();

    $enrollment->update(['status' => EnrollmentStatus::Cancelled]);
    expect(Gate::forUser($user)->allows('accessCourse', $course))->toBeFalse();

    $enrollment->update(['status' => EnrollmentStatus::Active]);
    $course->update(['status' => 'deactivated']);
    expect(Gate::forUser($user)->allows('accessCourse', $course))->toBeFalse();

    $course->update(['status' => 'published']);
    $student->update(['is_active' => false]);
    expect(Gate::forUser($user)->allows('accessCourse', $course))->toBeFalse();
});
