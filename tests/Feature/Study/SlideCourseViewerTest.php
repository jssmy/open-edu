<?php

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

function studentForSlideViewer(Course $course): array
{
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    return [$user, $student];
}

it('delivers course data with units and lessons for slide viewer', function () {
    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson1 = Lesson::factory()->create([
        'unit_id' => $unit->id,
        'position' => 1,
        'title' => 'Introducción Slide 1',
        'content_type' => ContentType::Text->value,
    ]);
    $lesson2 = Lesson::factory()->create([
        'unit_id' => $unit->id,
        'position' => 2,
        'title' => 'Tema 2 Slide 2',
        'content_type' => ContentType::Text->value,
    ]);

    [$user] = studentForSlideViewer($course);

    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.id', $course->id)
            ->has('course.units', 1)
            ->where('course.units.0.lessons.0.title', 'Introducción Slide 1')
            ->where('course.units.0.lessons.1.title', 'Tema 2 Slide 2')
            ->where('course.progress.total_lessons', 2)
        );
});
