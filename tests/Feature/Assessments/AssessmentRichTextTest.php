<?php

use App\Modules\Assessments\Infrastructure\Models\Assessment;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Identity\Domain\Role;
use Inertia\Testing\AssertableInertia as Assert;

function validAssessmentQuestion(array $statement): array
{
    return [
        'statement' => $statement,
        'points' => 1,
        'choices' => [
            ['text' => 'Correcta', 'is_correct' => true],
            ['text' => 'Incorrecta', 'is_correct' => false],
        ],
    ];
}

it('stores and returns instructions and question statements as rich documents', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();
    $instructions = richTextDocument('Lee cada pregunta con atención.');
    $statement = [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => '¿Cuál es la respuesta?',
                'marks' => [['type' => 'bold']],
            ]],
        ]],
    ];

    $this->actingAs($manager)->post("/courses/{$course->id}/assessments", [
        'title' => 'Evaluación enriquecida',
        'instructions' => $instructions,
        'max_attempts' => 1,
        'passing_score' => 1,
        'questions' => [validAssessmentQuestion($statement)],
    ])->assertRedirect();

    $assessment = Assessment::query()->sole();

    expect($assessment->instructions)->toEqual($instructions)
        ->and($assessment->questions()->sole()->statement)->toEqual($statement);

    $this->actingAs($manager)->get("/assessments/{$assessment->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Assessments/Edit')
            ->where('assessment.instructions', $instructions)
            ->where('assessment.questions.0.statement', $statement));
});

it('allows nullable instructions and rejects unsafe assessment rich content', function () {
    $manager = userWithRole(Role::GestorDeCursos);
    $course = Course::factory()->create();

    $this->actingAs($manager)->post("/courses/{$course->id}/assessments", [
        'title' => 'Sin instrucciones',
        'instructions' => null,
        'max_attempts' => 1,
        'passing_score' => 1,
        'questions' => [validAssessmentQuestion(richTextDocument('Pregunta válida'))],
    ])->assertRedirect();

    $this->actingAs($manager)->post("/courses/{$course->id}/assessments", [
        'title' => 'Pregunta insegura',
        'instructions' => null,
        'max_attempts' => 1,
        'passing_score' => 1,
        'questions' => [validAssessmentQuestion([
            'type' => 'doc',
            'content' => [['type' => 'script', 'attrs' => ['src' => 'https://evil.example/x.js']]],
        ])],
    ])->assertSessionHasErrors('questions.0.statement');

    $this->actingAs($manager)->post("/courses/{$course->id}/assessments", [
        'title' => 'Enlace inseguro',
        'instructions' => null,
        'max_attempts' => 1,
        'passing_score' => 1,
        'questions' => [validAssessmentQuestion([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Abrir',
                    'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]],
                ]],
            ]],
        ])],
    ])->assertSessionHasErrors('questions.0.statement');

    $this->assertDatabaseCount('assessments', 1);
});
