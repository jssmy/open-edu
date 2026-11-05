<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

function setupStudentWithOffering(array $offeringAttributes = []): array
{
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);

    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->for($unit)->create();

    $offering = CourseOffering::factory()->create(array_merge([
        'course_id' => $course->id,
        'group_code' => 'GRP-01',
        'status' => 'published',
    ], $offeringAttributes));

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    return [$user, $student, $course, $offering, $enrollment];
}

it('redirects student from /my/courses to /my/classes', function () {
    [$user] = setupStudentWithOffering();

    $this->actingAs($user)->get('/my/courses')
        ->assertRedirect(route('my.classes.index'));
});

it('lists student enrolled classes on /my/classes with state filters', function () {
    [$user, $student, $activeCourse, $activeOffering] = setupStudentWithOffering([
        'starts_on' => now()->subDays(5)->toDateString(),
        'ends_on' => now()->addDays(20)->toDateString(),
    ]);

    // Crear segunda oferta programada a futuro
    $futureCourse = Course::factory()->create(['status' => 'published']);
    $futureOffering = CourseOffering::factory()->create([
        'course_id' => $futureCourse->id,
        'group_code' => 'GRP-02',
        'starts_on' => now()->addDays(10)->toDateString(),
        'ends_on' => now()->addDays(40)->toDateString(),
    ]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $futureCourse->id,
        'course_offering_id' => $futureOffering->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($user)->get('/my/classes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Student/MyClasses')
            ->has('classes', 2)
            ->where('classes.0.status', 'in_progress')
            ->where('classes.1.status', 'scheduled')
            ->where('classes.1.can_access', false)
            ->where('filters.status', 'in_progress'));
});
