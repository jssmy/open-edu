<?php

namespace App\Modules\Progress\Application;

use App\Modules\Progress\Domain\ProgressRepository;
use App\Shared\Audit\Auditor;

final class IssueCourseCertificate
{
    public function __construct(
        private readonly ProgressRepository $progress,
        private readonly Auditor $auditor,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     file_url: string,
     *     download_url: string,
     *     issued_at: string
     * }
     */
    public function __invoke(int $courseId, int $userId, ?int $studentId = null): array
    {
        $certificate = $this->progress->issueCertificate($courseId, $userId, $studentId);

        $this->auditor->log('certificate.issued', $userId, "certificate:{$certificate['id']}", [
            'course_id' => $courseId,
            'user_id' => $userId,
            'student_id' => $studentId,
            'code' => $certificate['code'],
            'file_url' => $certificate['file_url'],
        ]);

        return $certificate;
    }
}
