<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class CreateAssessment
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

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
    public function __invoke(int $courseId, array $attributes): int
    {
        return $this->assessments->create($courseId, (new AssessmentData($attributes))->validated());
    }
}
