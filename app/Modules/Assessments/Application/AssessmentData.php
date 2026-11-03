<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\Assessment;

final readonly class AssessmentData
{
    /**
     * @param array{
     *     title: string,
     *     max_attempts: int|string,
     *     passing_score: int|string,
     *     questions: list<array{
     *         statement: string,
     *         points: int|string,
     *         choices: list<array{text: string, is_correct: bool|int|string}>
     *     }>
     * } $attributes
     */
    public function __construct(private array $attributes) {}

    /**
     * @return array{
     *     title: string,
     *     max_attempts: int,
     *     passing_score: int,
     *     questions: list<array{statement: string, points: int, choices: list<array{text: string, is_correct: bool}>}>
     * }
     */
    public function validated(): array
    {
        $attributes = [
            'title' => $this->attributes['title'],
            'max_attempts' => (int) $this->attributes['max_attempts'],
            'passing_score' => (int) $this->attributes['passing_score'],
            'questions' => array_map(
                fn (array $question): array => [
                    'statement' => $question['statement'],
                    'points' => (int) $question['points'],
                    'choices' => array_map(
                        fn (array $choice): array => [
                            'text' => $choice['text'],
                            'is_correct' => in_array($choice['is_correct'], [true, 1, '1'], true),
                        ],
                        $question['choices'],
                    ),
                ],
                $this->attributes['questions'],
            ),
        ];
        $totalScore = array_sum(array_column($attributes['questions'], 'points'));
        $assessment = new Assessment(
            $attributes['max_attempts'],
            $attributes['passing_score'],
            $totalScore,
        );
        $assessment->assertPublishable($attributes['questions']);

        return $attributes;
    }
}
