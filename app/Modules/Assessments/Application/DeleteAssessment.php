<?php

namespace App\Modules\Assessments\Application;

use App\Modules\Assessments\Domain\AssessmentRepository;

final class DeleteAssessment
{
    public function __construct(private readonly AssessmentRepository $assessments) {}

    public function __invoke(int $assessmentId): void
    {
        $this->assessments->delete($assessmentId);
    }
}
