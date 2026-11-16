<?php

namespace App\Modules\Progress\Application;

use App\Modules\Progress\Domain\CourseProgress;
use App\Modules\Progress\Domain\ProgressRepository;

final class GetCourseProgress
{
    public function __construct(private readonly ProgressRepository $progress) {}

    /** @return array{id: int, name: string, description: ?string, issues_certificate?: bool, all_assessments_passed?: bool, can_claim_certificate?: bool, certificate?: array{id: int, code: string, file_url: string, download_url: string, issued_at: string}|null, units: list<array{id: int, title: string, position: int, lessons: list<array{id: int, title: string, position: int, content_type: string, content: ?string, is_completed: bool}>}>, progress: array{completed_lessons: int, total_lessons: int, percentage: int}}|null */
    public function __invoke(int $courseId, ?int $studentId, ?int $userId = null): ?array
    {
        $course = $this->progress->courseStudyData($courseId, $studentId, $userId);

        if ($course === null) {
            return null;
        }

        $lessons = array_merge(...array_map(
            fn (array $unit): array => $unit['lessons'],
            $course['units'],
        ));
        $completedLessons = count(array_filter(
            $lessons,
            fn (array $lesson): bool => $lesson['is_completed'],
        ));
        $totalLessons = count($lessons);
        $courseProgress = (new CourseProgress($completedLessons, $totalLessons))->toArray();
        $course['progress'] = $courseProgress;

        $issuesCertificate = (bool) ($course['issues_certificate'] ?? true);
        $allAssessmentsPassed = (bool) ($course['all_assessments_passed'] ?? true);
        $course['can_claim_certificate'] = $issuesCertificate
            && $totalLessons > 0
            && $completedLessons >= $totalLessons
            && $allAssessmentsPassed;

        return $course;
    }
}
