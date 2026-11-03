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

it('completes lessons idempotently and excludes archived lessons from progress', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->for($unit)->create(['position' => 1]);
    Lesson::factory()->for($unit)->create(['position' => 2]);
    Lesson::factory()->for($unit)->create(['position' => 3, 'is_archived' => true]);

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertRedirect();
    $completedAt = DB::table('lesson_progress')
        ->where('student_id', $student->id)
        ->where('lesson_id', $lesson->id)
        ->value('completed_at');
    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertRedirect();

    $this->assertDatabaseCount('lesson_progress', 1);
    expect(DB::table('lesson_progress')->value('completed_at'))->toBe($completedAt);
    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.progress.completed_lessons', 1)
            ->where('course.progress.total_lessons', 2)
            ->where('course.progress.percentage', 50)
            ->where('course.units.0.lessons.0.is_completed', true)
            ->has('course.units.0.lessons', 2));
});

it('does not allow an archived lesson to be completed', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->for($unit)->create(['is_archived' => true]);

    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertNotFound();

    $this->assertDatabaseCount('lesson_progress', 0);
});
