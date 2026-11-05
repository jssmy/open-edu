<?php

namespace App\Modules\Progress\Application;

use App\Modules\Progress\Domain\CourseProgress;
use App\Modules\Progress\Domain\ProgressRepository;

final class GetMyCourses
{
    public function __construct(private readonly ProgressRepository $progress) {}

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     excerpt: string,
     *     cover_url: string,
     *     units_count: int,
     *     status: string,
     *     teachers: list<array{id: int, name: string}>,
     *     description: ?string,
     *     progress: array{completed_lessons: int, total_lessons: int, percentage: int, status: string}
     * }>
     */
    public function __invoke(int $studentId): array
    {
        return array_map(function (array $course): array {
            $courseProgress = new CourseProgress(
                $course['completed_lessons'],
                $course['total_lessons'],
            );

            return [
                'id' => $course['id'],
                'name' => $course['name'],
                'excerpt' => $course['excerpt'] ?? '',
                'cover_url' => $course['cover_url'] ?? '',
                'units_count' => $course['units_count'] ?? 0,
                'status' => $course['status'] ?? 'published',
                'teachers' => $course['teachers'] ?? [],
                'description' => $course['description'],
                'progress' => $courseProgress->toArray(),
            ];
        }, $this->progress->coursesForStudent($studentId));
    }
}
