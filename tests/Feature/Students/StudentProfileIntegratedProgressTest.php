<?php

use App\Models\User;
use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Attempt;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('teacher can view student profile with integrated progress and assessments for their courses', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('docente');

    $studentUser = User::factory()->create();
    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'identifier' => 'STU-100',
        'phone' => '123456789',
        'is_active' => true,
    ]);

    // Course 1: Assigned to teacher
    $courseA = Course::factory()->create(['name' => 'Curso de Docente']);
    $courseA->teachers()->attach($teacher->id);

    $unit = Unit::query()->create(['course_id' => $courseA->id, 'title' => 'Unidad 1', 'position' => 1]);
    $lesson1 = Lesson::query()->create(['unit_id' => $unit->id, 'title' => 'L1', 'position' => 1, 'content_type' => 'text']);
    $lesson2 = Lesson::query()->create(['unit_id' => $unit->id, 'title' => 'L2', 'position' => 2, 'content_type' => 'text']);

    // Student completed lesson1
    DB::table('lesson_progress')->insert([
        'student_id' => $student->id,
        'lesson_id' => $lesson1->id,
        'completed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Assessment with attempt
    $assessment = Assessment::query()->create([
        'course_id' => $courseA->id,
        'title' => 'Examen Unidad 1',
        'instructions' => [],
        'max_attempts' => 2,
        'passing_score' => 11,
        'total_score' => 20,
        'is_published' => true,
    ]);

    Attempt::query()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'score' => 16,
        'passed' => true,
        'status' => 'submitted',
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $courseA->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDays(5),
    ]);

    // Course 2: NOT assigned to teacher
    $courseB = Course::factory()->create(['name' => 'Otro Curso Ajeno']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $courseB->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDays(2),
    ]);

    $this->actingAs($teacher)
        ->get("/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Students/Show')
            ->where('student.id', $student->id)
            ->has('courses', 1)
            ->where('courses.0.name', 'Curso de Docente')
            ->where('courses.0.progress.completed_lessons', 1)
            ->where('courses.0.progress.total_lessons', 2)
            ->where('courses.0.progress.percentage', 50)
            ->has('courses.0.assessments', 1)
            ->where('courses.0.assessments.0.title', 'Examen Unidad 1')
            ->where('courses.0.assessments.0.latest_attempt.score', 16)
            ->where('courses.0.assessments.0.latest_attempt.passed', true)
        );
});

test('teacher cannot view student profile if student is not in any of their courses', function (): void {
    $teacher = User::factory()->create();
    $teacher->assignRole('docente');

    $studentUser = User::factory()->create();
    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'identifier' => 'STU-999',
        'is_active' => true,
    ]);

    $course = Course::factory()->create(['name' => 'Curso Sin Este Docente']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active->value,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->get("/students/{$student->id}")
        ->assertNotFound();
});
