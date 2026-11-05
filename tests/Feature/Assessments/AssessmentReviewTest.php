<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Attempt;
use App\Modules\Assessments\Infrastructure\Models\AttemptAnswer;
use App\Modules\Assessments\Infrastructure\Models\Choice;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Identity\Domain\Role;
use App\Modules\Students\Infrastructure\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;

test('teacher can review completed assessment attempt for a student with question choices and scores', function (): void {
    $teacher = userWithRole(Role::Docente);
    $studentUser = userWithRole(Role::Estudiante);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $course = Course::factory()->create();
    $course->teachers()->sync([$teacher->id]);

    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => EnrollmentStatus::Active->value,
    ]);

    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Examen Parcial',
        'is_published' => true,
        'total_score' => 20,
        'passing_score' => 11,
        'max_attempts' => 2,
    ]);

    $question = Question::query()->create([
        'assessment_id' => $assessment->id,
        'statement' => ['type' => 'doc', 'content' => []],
        'points' => 20,
        'position' => 1,
    ]);

    $correctChoice = Choice::query()->create([
        'question_id' => $question->id,
        'text' => 'Correcta',
        'is_correct' => true,
    ]);

    $incorrectChoice = Choice::query()->create([
        'question_id' => $question->id,
        'text' => 'Incorrecta',
        'is_correct' => false,
    ]);

    $attempt = Attempt::query()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'score' => 20,
        'passed' => true,
        'started_at' => now()->subHour(),
        'submitted_at' => now(),
    ]);

    AttemptAnswer::create([
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'choice_id' => $correctChoice->id,
    ]);

    $this->actingAs($teacher)
        ->get("/assessments/{$assessment->id}/review?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Assessments/Review')
            ->where('assessment.id', $assessment->id)
            ->where('student.id', $student->id)
            ->where('attempt.status', 'completed')
            ->where('attempt.score', 20)
            ->where('canContinue', false)
            ->where('isStudent', false)
            ->has('questions', 1)
            ->where('questions.0.student_answer.choice_id', $correctChoice->id)
            ->where('questions.0.student_answer.is_correct', true)
        );
});

test('student can review their in_progress assessment and see canContinue as true', function (): void {
    $studentUser = userWithRole(Role::Estudiante);
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $course = Course::factory()->create(['status' => 'published']);

    Enrollment::factory()->create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => EnrollmentStatus::Active->value,
    ]);

    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Examen en curso',
        'is_published' => true,
        'total_score' => 10,
        'passing_score' => 6,
        'max_attempts' => 1,
    ]);

    $attempt = Attempt::query()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($studentUser)
        ->get("/assessments/{$assessment->id}/review")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Assessments/Review')
            ->where('assessment.id', $assessment->id)
            ->where('attempt.status', 'in_progress')
            ->where('isStudent', true)
            ->where('canContinue', true)
        );
});
