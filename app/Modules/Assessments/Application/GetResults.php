<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class GetResults
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

    /**
     * @return array{
     *     result: array{
     *         assessment_id: int,
     *         assessment_title: string,
     *         attempt_number: int,
     *         score: int,
     *         total_score: int,
     *         passed: bool,
     *         submitted_at: string
     *     }|null,
     *     results: list<array{
     *         student_name: string,
     *         attempt_number: int,
     *         score: int,
     *         total_score: int,
     *         passed: bool,
     *         submitted_at: string
     *     }>
     * }
     */
    public function forAssessment(int $assessmentId, ?int $studentId): array
    {
        return [
            'result' => $studentId === null
                ? null
                : $this->assessments->latestResult($assessmentId, $studentId),
            'results' => $studentId === null
                ? $this->assessments->resultsForAssessment($assessmentId)
                : [],
        ];
    }

    /**
     * @return list<array{
     *     assessment_id: int,
     *     assessment_title: string,
     *     attempt_number: int,
     *     score: int,
     *     total_score: int,
     *     passed: bool,
     *     submitted_at: string
     * }>
     */
    public function forStudent(int $studentId): array
    {
        return $this->assessments->resultsForStudent($studentId);
    }
}
