<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Assessments\Infrastructure\Models\Attempt;
use App\Modules\Assessments\Infrastructure\Models\AttemptAnswer;
use App\Modules\Assessments\Infrastructure\Models\Question;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Infrastructure\Models\Student;
use Database\Seeders\AssessmentSeeder;

it('seeds assessments, questions, choices and student attempts with correct and incorrect answers', function () {
    $course = Course::factory()->published()->create([
        'name' => 'Curso de Python Especializado',
    ]);

    $student1 = Student::factory()->create();
    $student2 = Student::factory()->create();

    Enrollment::query()->create([
        'student_id' => $student1->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    Enrollment::query()->create([
        'student_id' => $student2->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $this->seed(AssessmentSeeder::class);

    expect(Assessment::query()->where('course_id', $course->id)->count())->toBeGreaterThan(0);

    $assessment = Assessment::query()->where('course_id', $course->id)->firstOrFail();

    expect(Question::query()->where('assessment_id', $assessment->id)->count())->toBeGreaterThan(0)
        ->and(Attempt::query()->where('assessment_id', $assessment->id)->count())->toBeGreaterThan(0)
        ->and(AttemptAnswer::query()->whereHas('attempt', fn ($q) => $q->where('assessment_id', $assessment->id))->count())->toBeGreaterThan(0);

    // Verify there are both passed and failed attempts or both correct and incorrect choices selected
    $attempts = Attempt::query()->where('assessment_id', $assessment->id)->get();
    expect($attempts)->isNotEmpty();

    $hasCorrectAnswer = AttemptAnswer::query()
        ->whereHas('attempt', fn ($q) => $q->where('assessment_id', $assessment->id))
        ->whereHas('choice', fn ($q) => $q->where('is_correct', true))
        ->exists();

    $hasIncorrectAnswer = AttemptAnswer::query()
        ->whereHas('attempt', fn ($q) => $q->where('assessment_id', $assessment->id))
        ->whereHas('choice', fn ($q) => $q->where('is_correct', false))
        ->exists();

    expect($hasCorrectAnswer)->toBeTrue()
        ->and($hasIncorrectAnswer)->toBeTrue();
});
