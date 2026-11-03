<?php

namespace App\Modules\Assessments\Domain;

use App\Shared\Exceptions\DomainException;

final readonly class Assessment
{
    public function __construct(
        public int $maxAttempts,
        public int $passingScore,
        public int $totalScore,
    ) {
        if ($maxAttempts < 1) {
            throw new DomainException('La evaluación debe permitir al menos un intento.', 'max_attempts');
        }

        if ($totalScore < 0 || $passingScore < 0 || $passingScore > $totalScore) {
            throw new DomainException(
                'La nota mínima debe estar entre cero y el puntaje total.',
                'passing_score',
            );
        }
    }

    public function nextAttemptNumber(int $attemptsTaken): int
    {
        if ($attemptsTaken < 0 || $attemptsTaken >= $this->maxAttempts) {
            throw new DomainException('No quedan intentos disponibles.', 'assessment');
        }

        return $attemptsTaken + 1;
    }

    /**
     * @param  list<array{points: int, is_correct: bool}>  $answers
     * @return array{score: int, passed: bool}
     */
    public function grade(array $answers): array
    {
        $score = array_sum(array_map(
            fn (array $answer): int => $answer['is_correct'] ? $answer['points'] : 0,
            $answers,
        ));

        return [
            'score' => $score,
            'passed' => $score >= $this->passingScore,
        ];
    }

    /**
     * @param  list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>  $questions
     */
    public function assertPublishable(array $questions): void
    {
        if ($questions === []) {
            throw new DomainException('Agrega al menos una pregunta válida antes de publicar.', 'questions');
        }

        foreach ($questions as $question) {
            new Question($question['statement'], $question['points'], $question['choices']);
        }
    }
}
