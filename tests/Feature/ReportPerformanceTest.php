<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

it('renders progress for 500 enrolled students within 30 seconds', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->published()->create();
    $students = Student::factory()->count(500)->create();

    foreach ($students as $student) {
        Enrollment::factory()->for($student)->for($course)->create();
    }

    $unit = Unit::factory()->for($course)->create();
    Lesson::factory()->for($unit)->create();

    $startedAt = hrtime(true);
    $response = $this->actingAs($manager)->get("/courses/{$course->id}/progress");
    $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Progress/Course')
            ->has('students', 500));

    expect($elapsedSeconds)->toBeLessThan(30);
});

it('returns a graded assessment result within five seconds', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->published()->create();
    Enrollment::factory()->for($student)->for($course)->create([
        'status' => EnrollmentStatus::Active,
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación de rendimiento',
        'max_attempts' => 1,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => true,
    ]);
    $question = $assessment->questions()->create([
        'statement' => richTextDocument('Pregunta de prueba'),
        'points' => 1,
        'position' => 1,
    ]);
    $correctChoice = $question->choices()->create([
        'text' => 'Correcta',
        'is_correct' => true,
    ]);
    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();

    $startedAt = hrtime(true);
    $response = $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $correctChoice->id],
    ]);
    $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

    $response->assertRedirect("/assessments/{$assessment->id}/results");
    expect($elapsedSeconds)->toBeLessThan(5);
});

it('reflects lesson completion in course progress within one minute', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->published()->create();
    Enrollment::factory()->for($student)->for($course)->create([
        'status' => EnrollmentStatus::Active,
    ]);
    $unit = Unit::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($unit)->create();

    $startedAt = hrtime(true);
    $this->actingAs($user)->post("/lessons/{$lesson->id}/complete")->assertRedirect();
    $this->actingAs($user)->get("/my/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Study/CourseViewer')
            ->where('course.units.0.lessons.0.is_completed', true));
    $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

    expect($elapsedSeconds)->toBeLessThan(60);
});
