<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

it('allows individual progress in async course without affecting other enrolled users', function () {
    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);

    // Usuario 1: Docente autoinscrito a capacitación offline (REQ-034)
    $teacherUser = userWithRole(Role::Docente, ['must_change_password' => false]);
    Enrollment::query()->create([
        'user_id' => $teacherUser->id,
        'student_id' => null,
        'course_id' => $course->id,
        'course_offering_id' => null,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    // Usuario 2: Estudiante en curso libre asíncrono
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    Enrollment::query()->create([
        'user_id' => null,
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_offering_id' => null,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    // Docente ve canMarkComplete en true
    $this->actingAs($teacherUser)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('canMarkComplete', true)
            ->where('course.units.0.lessons.0.is_completed', false));

    // Docente marca lección como completada
    $response = $this->actingAs($teacherUser)->postJson("/lessons/{$lesson->id}/complete");
    $response->assertOk();

    // Docente ahora ve la lección completada
    $this->actingAs($teacherUser)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.units.0.lessons.0.is_completed', true));

    // Estudiante 2 NO debe ver la lección completada (su avance es independiente)
    $this->actingAs($studentUser)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('canMarkComplete', true)
            ->where('course.units.0.lessons.0.is_completed', false));
});
