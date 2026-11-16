<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('shows lessons completed by teacher to all enrolled students in course offering', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 2]);

    $offering = CourseOffering::query()->create([
        'course_id' => $course->id,
        'group_code' => 'GRUPO-SYNC',
        'status' => 'published',
    ]);
    $offering->teachers()->attach($teacher->id);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    // Docente marca lección 1 como completada para la oferta
    DB::table('lesson_progress')->insert([
        'course_offering_id' => $offering->id,
        'user_id' => $teacher->id,
        'student_id' => null,
        'lesson_id' => $lesson1->id,
        'completed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Estudiante visita el curso y ve la lección 1 completada y la lección 2 pendiente
    $this->actingAs($studentUser)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.units.0.lessons.0.is_completed', true)
            ->where('course.units.0.lessons.1.is_completed', false)
            ->where('course.progress.completed_lessons', 1)
            ->where('canMarkComplete', false));
});
