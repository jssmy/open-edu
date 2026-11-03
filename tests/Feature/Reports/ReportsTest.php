<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Attempt;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('filters enrollment and progress reports and streams safe CSV output', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create(['status' => 'published']);
    $formulaNameStudent = userWithRole(Role::Estudiante, [
        'name' => '=2+2',
        'must_change_password' => false,
    ]);
    $student = Student::factory()->create(['user_id' => $formulaNameStudent->id]);
    $activeEnrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $secondUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $secondStudent = Student::factory()->create(['user_id' => $secondUser->id]);
    Enrollment::query()->create([
        'student_id' => $secondStudent->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Suspended,
        'enrolled_at' => now(),
    ]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->for($unit)->create();
    DB::table('lesson_progress')->insert([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'completed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($manager)->get("/reports/enrollments?course_id={$course->id}&status=active")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports/Index')
            ->where('report', 'enrollments')
            ->has('rows', 1)
            ->where('rows.0.id', $activeEnrollment->id)
            ->where('rows.0.student_name', '=2+2')
            ->where('rows.0.status', 'active'));

    $this->actingAs($manager)->get("/reports/progress?course_id={$course->id}&student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports/Index')
            ->where('report', 'progress')
            ->has('rows', 1)
            ->where('rows.0.completed_lessons', 1)
            ->where('rows.0.total_lessons', 1)
            ->where('rows.0.percentage', 100));

    $response = $this->actingAs($manager)
        ->get("/reports/enrollments?course_id={$course->id}&status=active&format=csv")
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename=enrollments.csv');
    ob_start();
    $response->baseResponse->sendContent();
    $csv = ob_get_clean();

    expect($csv)
        ->toContain('Estudiante')
        ->toContain("'=2+2");
});

it('rejects filters that do not apply to the selected report', function () {
    $manager = userWithRole(Role::GestorDeCursos);

    $this->actingAs($manager)
        ->get('/reports/results?status=active')
        ->assertSessionHasErrors('status');
    $this->actingAs($manager)
        ->get('/reports/enrollments?passed=1')
        ->assertSessionHasErrors('passed');
});

it('shows only latest assessment results and limits reports to assigned teacher courses', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['status' => 'published']);
    $course->teachers()->attach($teacher->id);
    $otherCourse = Course::factory()->create(['status' => 'published']);
    $studentUser = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación final',
        'max_attempts' => 2,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => true,
    ]);
    Attempt::query()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'score' => 1,
        'passed' => true,
        'status' => 'submitted',
        'started_at' => now()->subMinutes(10),
        'submitted_at' => now()->subMinutes(5),
    ]);
    Attempt::query()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'score' => 0,
        'passed' => false,
        'status' => 'submitted',
        'started_at' => now()->subMinutes(4),
        'submitted_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->get("/reports/results?course_id={$course->id}&passed=0")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports/Index')
            ->where('report', 'results')
            ->has('rows', 1)
            ->where('rows.0.attempt_number', 2)
            ->where('rows.0.score', 0)
            ->where('rows.0.passed', false));

    $this->actingAs($teacher)
        ->get("/reports/results?course_id={$course->id}&format=csv")
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=results.csv');
    $this->actingAs($teacher)->get("/reports/enrollments?course_id={$otherCourse->id}")
        ->assertNotFound();
});
