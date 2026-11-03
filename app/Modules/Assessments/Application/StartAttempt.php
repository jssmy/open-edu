<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class StartAttempt
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

    /** @return array{id: int, attempt_number: int} */
    public function __invoke(int $assessmentId, int $studentId): array
    {
        return $this->assessments->startAttempt($assessmentId, $studentId);
    }
}
