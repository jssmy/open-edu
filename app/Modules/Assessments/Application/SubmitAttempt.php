<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class SubmitAttempt
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

    /**
     * @param  array<int, int>  $answers
     * @return array{
     *     assessment_id: int,
     *     assessment_title: string,
     *     attempt_number: int,
     *     score: int,
     *     total_score: int,
     *     passed: bool,
     *     submitted_at: string
     * }
     */
    public function __invoke(int $assessmentId, int $studentId, array $answers): array
    {
        return $this->assessments->submitAttempt($assessmentId, $studentId, $answers);
    }
}
