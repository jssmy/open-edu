<?php

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('shows course progress to a manager with lesson totals for each enrolled student', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create(['status' => 'published']);
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $completedLesson = Lesson::factory()->for($unit)->create(['position' => 1]);
    Lesson::factory()->for($unit)->create(['position' => 2]);
    Lesson::factory()->for($unit)->create(['position' => 3, 'is_archived' => true]);
    DB::table('lesson_progress')->insert([
        'student_id' => $student->id,
        'lesson_id' => $completedLesson->id,
        'completed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($manager)->get("/courses/{$course->id}/progress")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Progress/Course')
            ->where('course.id', $course->id)
            ->has('students', 1)
            ->where('students.0.enrollment_status', 'active')
            ->where('students.0.completed_lessons', 1)
            ->where('students.0.total_lessons', 2)
            ->where('students.0.percentage', 50));
});

it('limits course progress to courses assigned to a teacher', function () {
    $teacher = userWithRole(Role::Docente);
    $assignedCourse = Course::factory()->create(['status' => 'published']);
    $otherCourse = Course::factory()->create(['status' => 'published']);
    $assignedCourse->teachers()->attach($teacher->id);

    $this->actingAs($teacher)->get("/courses/{$assignedCourse->id}/progress")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Progress/Course')
            ->where('course.id', $assignedCourse->id));
    $this->actingAs($teacher)->get("/courses/{$otherCourse->id}/progress")->assertNotFound();
});

it('shows a student only their own progress and preserves cancelled enrollment history', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $activeCourse = Course::factory()->create([
        'name' => 'Active course',
        'status' => 'published',
    ]);
    $cancelledCourse = Course::factory()->create([
        'name' => 'Cancelled course',
        'status' => 'deactivated',
    ]);
    foreach ([
        [$activeCourse, EnrollmentStatus::Active],
        [$cancelledCourse, EnrollmentStatus::Cancelled],
    ] as [$course, $status]) {
        Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    $otherUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $otherStudent = Student::factory()->create(['user_id' => $otherUser->id]);
    $otherCourse = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $otherStudent->id,
        'course_id' => $otherCourse->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($user)->get('/my/progress')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Progress/MyProgress')
            ->has('courses', 2)
            ->where('courses.0.course_id', $activeCourse->id)
            ->where('courses.1.course_id', $cancelledCourse->id)
            ->where('courses.1.enrollment_status', 'cancelled'));
});
