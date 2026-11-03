<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

it('grades a submitted attempt and shows its result', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación de biología',
        'max_attempts' => 2,
        'passing_score' => 2,
        'total_score' => 2,
        'is_published' => true,
    ]);
    $question = $assessment->questions()->create([
        'statement' => '¿Qué estudia la biología?',
        'points' => 2,
        'position' => 1,
    ]);
    $correctChoice = $question->choices()->create([
        'text' => 'Los seres vivos',
        'is_correct' => true,
    ]);
    $question->choices()->create([
        'text' => 'Los minerales',
        'is_correct' => false,
    ]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Assessments/Take')
            ->where('assessment.id', $assessment->id)
            ->where('assessment.questions.0.statement', '¿Qué estudia la biología?')
            ->missing('assessment.questions.0.choices.0.is_correct'));
    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();

    $this->assertDatabaseCount('attempts', 1);

    $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $correctChoice->id],
    ])->assertRedirect("/assessments/{$assessment->id}/results");

    $this->assertDatabaseHas('attempts', [
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'score' => 2,
        'passed' => true,
        'status' => 'submitted',
    ]);
    $this->assertDatabaseHas('attempt_answers', [
        'question_id' => $question->id,
        'choice_id' => $correctChoice->id,
    ]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/results")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Assessments/Results')
            ->where('result.score', 2)
            ->where('result.passed', true)
            ->where('result.attempt_number', 1));
});

it('uses the latest submitted attempt as the official result', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación con reintento',
        'max_attempts' => 2,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => true,
    ]);
    $question = $assessment->questions()->create([
        'statement' => 'Pregunta',
        'points' => 1,
        'position' => 1,
    ]);
    $correctChoice = $question->choices()->create(['text' => 'Correcta', 'is_correct' => true]);
    $wrongChoice = $question->choices()->create(['text' => 'Incorrecta', 'is_correct' => false]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();
    $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $correctChoice->id],
    ])->assertRedirect();
    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();
    $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $wrongChoice->id],
    ])->assertRedirect();

    $this->actingAs($user)->get("/assessments/{$assessment->id}/results")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Assessments/Results')
            ->where('result.score', 0)
            ->where('result.passed', false)
            ->where('result.attempt_number', 2));
});

it('forbids starting another attempt after reaching the configured limit', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación de un intento',
        'max_attempts' => 1,
        'passing_score' => 0,
        'total_score' => 0,
        'is_published' => true,
    ]);
    $question = $assessment->questions()->create([
        'statement' => 'Pregunta',
        'points' => 1,
        'position' => 1,
    ]);
    $choice = $question->choices()->create(['text' => 'Opción', 'is_correct' => true]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertOk();
    $this->actingAs($user)->post("/assessments/{$assessment->id}/attempts", [
        'answers' => [$question->id => $choice->id],
    ])->assertRedirect();

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertForbidden();

    $this->assertDatabaseCount('attempts', 1);
});

it('forbids starting an assessment without an active enrollment', function () {
    $user = userWithRole(Role::Estudiante, ['must_change_password' => false]);
    $student = Student::factory()->create(['user_id' => $user->id]);
    $course = Course::factory()->create(['status' => 'published']);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Suspended,
        'enrolled_at' => now(),
    ]);
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación restringida',
        'max_attempts' => 1,
        'passing_score' => 0,
        'total_score' => 0,
        'is_published' => true,
    ]);

    $this->actingAs($user)->get("/assessments/{$assessment->id}/take")->assertForbidden();

    $this->assertDatabaseCount('attempts', 0);
});
