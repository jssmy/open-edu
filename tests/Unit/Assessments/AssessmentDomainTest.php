<?php

use App\Modules\Assessments\Domain\Assessment;
use App\Modules\Assessments\Domain\Attempt;
use App\Modules\Assessments\Domain\Question;
use App\Shared\Exceptions\DomainException;

it('grades correct answers and applies the passing score', function () {
    $assessment = new Assessment(
        maxAttempts: 2,
        passingScore: 3,
        totalScore: 5,
    );

    expect($assessment->grade([
        ['points' => 3, 'is_correct' => true],
        ['points' => 2, 'is_correct' => false],
    ]))->toBe([
        'score' => 3,
        'passed' => true,
    ]);

    expect($assessment->grade([
        ['points' => 3, 'is_correct' => false],
        ['points' => 2, 'is_correct' => true],
    ]))->toBe([
        'score' => 2,
        'passed' => false,
    ]);
});

it('allows only attempt numbers up to the configured maximum', function () {
    $assessment = new Assessment(
        maxAttempts: 2,
        passingScore: 1,
        totalScore: 1,
    );

    expect($assessment->nextAttemptNumber(1))->toBe(2);
    expect(fn () => $assessment->nextAttemptNumber(2))
        ->toThrow(DomainException::class, 'No quedan intentos disponibles.');
    expect(new Attempt(2, 2)->attemptNumber)->toBe(2);
    expect(fn () => new Attempt(3, 2))
        ->toThrow(DomainException::class, 'No quedan intentos disponibles.');
});

it('rejects a passing score outside the assessment total', function () {
    expect(fn () => new Assessment(
        maxAttempts: 1,
        passingScore: 2,
        totalScore: 1,
    ))->toThrow(DomainException::class, 'La nota mínima debe estar entre cero y el puntaje total.');
});

it('requires at least two alternatives and exactly one correct alternative', function (array $choices) {
    expect(fn () => new Question('Pregunta', 1, $choices))
        ->toThrow(DomainException::class);
})->with([
    'one alternative' => [[['text' => 'Única', 'is_correct' => true]]],
    'no correct alternative' => [[
        ['text' => 'Primera', 'is_correct' => false],
        ['text' => 'Segunda', 'is_correct' => false],
    ]],
    'multiple correct alternatives' => [[
        ['text' => 'Primera', 'is_correct' => true],
        ['text' => 'Segunda', 'is_correct' => true],
    ]],
]);
