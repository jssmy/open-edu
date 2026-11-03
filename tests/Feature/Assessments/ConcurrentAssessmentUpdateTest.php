<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

it('exposes the assessment version on its independent edit page', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $assessment = Assessment::query()->create([
        'course_id' => Course::factory()->create()->id,
        'title' => 'Evaluación',
        'max_attempts' => 1,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => false,
    ]);

    $this->actingAs($manager)->get("/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Assessments/Edit')
            ->where('assessment.lock_version', 1));
});

it('returns HTTP 409 without replacing assessment questions or choices after a stale update', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $assessment = Assessment::query()->create([
        'course_id' => Course::factory()->create()->id,
        'title' => 'Evaluación original',
        'instructions' => richTextDocument('Instrucciones originales'),
        'max_attempts' => 1,
        'passing_score' => 1,
        'total_score' => 1,
        'is_published' => false,
    ]);
    $question = $assessment->questions()->create([
        'statement' => richTextDocument('Pregunta original'),
        'points' => 1,
        'position' => 1,
    ]);
    $question->choices()->createMany([
        ['text' => 'Respuesta original', 'is_correct' => true],
        ['text' => 'Distractor original', 'is_correct' => false],
    ]);

    $this->actingAs($manager)->putJson("/assessments/{$assessment->id}", [
        'title' => 'Evaluación actualizada',
        'instructions' => richTextDocument('Instrucciones actuales'),
        'max_attempts' => 1,
        'passing_score' => 1,
        'lock_version' => 1,
        'questions' => [[
            'statement' => richTextDocument('Pregunta actualizada'),
            'points' => 1,
            'choices' => [
                ['text' => 'Respuesta actualizada', 'is_correct' => true],
                ['text' => 'Distractor actualizado', 'is_correct' => false],
            ],
        ]],
    ])->assertRedirect();

    $this->actingAs($manager)->putJson("/assessments/{$assessment->id}", [
        'title' => 'Versión obsoleta',
        'instructions' => richTextDocument('Instrucciones obsoletas'),
        'max_attempts' => 1,
        'passing_score' => 1,
        'lock_version' => 1,
        'questions' => [[
            'statement' => richTextDocument('Pregunta obsoleta'),
            'points' => 1,
            'choices' => [
                ['text' => 'Respuesta obsoleta', 'is_correct' => true],
                ['text' => 'Distractor obsoleto', 'is_correct' => false],
            ],
        ]],
    ])->assertStatus(409)
        ->assertJsonPath('message', 'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.');

    $savedAssessment = $assessment->fresh();
    $savedQuestion = $savedAssessment->questions()->with('choices')->sole();

    expect($savedAssessment->title)->toBe('Evaluación actualizada')
        ->and($savedAssessment->instructions)->toEqual(richTextDocument('Instrucciones actuales'))
        ->and($savedAssessment->lock_version)->toBe(2)
        ->and($savedQuestion->statement)->toEqual(richTextDocument('Pregunta actualizada'))
        ->and($savedQuestion->choices->pluck('text')->all())
        ->toBe(['Respuesta actualizada', 'Distractor actualizado']);
});
