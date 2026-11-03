<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;

it('creates an assessment with its questions and publishes it for an assigned teacher', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create(['status' => 'published']);
    $course->teachers()->attach($teacher);

    $this->actingAs($teacher)->post("/courses/{$course->id}/assessments", [
        'title' => 'Evaluación de biología',
        'max_attempts' => 2,
        'passing_score' => 3,
        'questions' => [
            [
                'statement' => '¿Qué estudia la biología?',
                'points' => 3,
                'choices' => [
                    ['text' => 'Los seres vivos', 'is_correct' => true],
                    ['text' => 'Los minerales', 'is_correct' => false],
                ],
            ],
        ],
    ])->assertRedirect();

    $assessment = Assessment::query()->sole();

    $this->assertDatabaseHas('assessments', [
        'id' => $assessment->id,
        'course_id' => $course->id,
        'title' => 'Evaluación de biología',
        'max_attempts' => 2,
        'passing_score' => 3,
        'total_score' => 3,
        'is_published' => false,
    ]);
    $this->assertDatabaseCount('questions', 1);
    $this->assertDatabaseCount('choices', 2);

    $this->actingAs($teacher)->patch("/assessments/{$assessment->id}/publish")
        ->assertRedirect();

    expect($assessment->fresh()->is_published)->toBeTrue();
});

it('rejects publishing an assessment without a valid question', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();
    $assessment = Assessment::query()->create([
        'course_id' => $course->id,
        'title' => 'Evaluación vacía',
        'max_attempts' => 1,
        'passing_score' => 0,
        'total_score' => 0,
        'is_published' => false,
    ]);

    $this->actingAs($manager)->patch("/assessments/{$assessment->id}/publish")
        ->assertSessionHasErrors('questions');

    expect($assessment->fresh()->is_published)->toBeFalse();
});

it('forbids students from managing assessments', function () {
    $student = userWithRole(Role::Estudiante);
    $course = Course::factory()->create();

    $this->actingAs($student)->post("/courses/{$course->id}/assessments", [])
        ->assertForbidden();
});

it('hides assessment management for a teacher who is not assigned to the course', function () {
    $teacher = userWithRole(Role::Docente);
    $course = Course::factory()->create();

    $this->actingAs($teacher)->post("/courses/{$course->id}/assessments", [])
        ->assertNotFound();

    $this->assertDatabaseCount('assessments', 0);
});
