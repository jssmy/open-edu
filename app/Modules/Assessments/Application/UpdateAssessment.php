<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class UpdateAssessment
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

    /**
     * @param array{
     *     title: string,
     *     instructions?: array|null,
     *     max_attempts: int|string,
     *     passing_score: int|string,
     *     questions: list<array{
     *         statement: array,
     *         points: int|string,
     *         choices: list<array{text: string, is_correct: bool|int|string}>
     *     }>
     * } $attributes
     */
    public function __invoke(int $assessmentId, int $expectedVersion, array $attributes): void
    {
        $this->assessments->update(
            $assessmentId,
            $expectedVersion,
            (new AssessmentData($attributes))->validated(),
        );
    }
}
