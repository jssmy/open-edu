<?php

namespace App\Modules\Progress\Application;

use App\Modules\Progress\Domain\CourseProgress;
use App\Modules\Progress\Domain\ProgressRepository;

final class GetCourseProgress
{
    public function __construct(private readonly ProgressRepository $progress) {}

    /** @return array{id: int, name: string, description: ?string, units: list<array{id: int, title: string, position: int, lessons: list<array{id: int, title: string, position: int, content_type: string, content: ?string, is_completed: bool}>}>, progress: array{completed_lessons: int, total_lessons: int, percentage: int}}|null */
    public function __invoke(int $courseId, int $studentId): ?array
    {
        $course = $this->progress->courseStudyData($courseId, $studentId);

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
        $course['progress'] = (new CourseProgress($completedLessons, count($lessons)))->toArray();

        return $course;
    }
}
