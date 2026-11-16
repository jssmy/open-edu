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

it('provides lesson completion state for teacher class viewer so frontend can resume at last completed lesson', function () {
    $teacher = userWithRole(Role::Docente, ['must_change_password' => false]);
    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 2]);
    $lesson3 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 3]);

    $offering = CourseOffering::query()->create([
        'course_id' => $course->id,
        'group_code' => 'GRUPO-RESUME',
        'status' => 'published',
    ]);
    $offering->teachers()->attach($teacher->id);

    // Marcar lecciones 1 y 2 para la oferta
    DB::table('lesson_progress')->insert([
        [
            'course_offering_id' => $offering->id,
            'user_id' => $teacher->id,
            'student_id' => null,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'course_offering_id' => $offering->id,
            'user_id' => $teacher->id,
            'student_id' => null,
            'lesson_id' => $lesson2->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->actingAs($teacher)->get("/my/classes/{$offering->id}/viewer")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('isTeacherView', true)
            ->where('course.units.0.lessons.0.is_completed', true)
            ->where('course.units.0.lessons.1.is_completed', true)
            ->where('course.units.0.lessons.2.is_completed', false)
            ->where('course.progress.completed_lessons', 2)
            ->where('course.progress.total_lessons', 3));
});

it('provides lesson completion state for student viewer so frontend can resume at last completed lesson', function () {
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $course = Course::factory()->create(['status' => 'published']);
    $unit = Unit::factory()->create(['course_id' => $course->id, 'position' => 1]);
    $lesson1 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 1]);
    $lesson2 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 2]);
    $lesson3 = Lesson::factory()->create(['unit_id' => $unit->id, 'position' => 3]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_offering_id' => null,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    // Marcar lecciones 1 y 2
    DB::table('lesson_progress')->insert([
        [
            'course_offering_id' => null,
            'user_id' => $studentUser->id,
            'student_id' => $student->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'course_offering_id' => null,
            'user_id' => $studentUser->id,
            'student_id' => $student->id,
            'lesson_id' => $lesson2->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->actingAs($studentUser)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.units.0.lessons.0.is_completed', true)
            ->where('course.units.0.lessons.1.is_completed', true)
            ->where('course.units.0.lessons.2.is_completed', false)
            ->where('course.progress.completed_lessons', 2)
            ->where('course.progress.total_lessons', 3));
});
