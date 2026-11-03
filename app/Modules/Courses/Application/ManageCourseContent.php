<?php

namespace App\Modules\Courses\Application;

use App\Modules\Courses\Domain\CourseRepository;

class ManageCourseContent
{
    public function __construct(private readonly CourseRepository $courses) {}

    /** @param array{course_id: int, title: string, position: int} $attributes */
    public function createUnit(array $attributes): int
    {
        return $this->courses->createUnit($attributes);
    }

    /** @param array{title: string, position: int} $attributes */
    public function updateUnit(int $unitId, array $attributes): void
    {
        $this->courses->updateUnit($unitId, $attributes);
    }

    public function deleteUnit(int $unitId): void
    {
        $this->courses->deleteUnit($unitId);
    }

    /** @param list<int> $unitIds */
    public function reorderUnits(array $unitIds): void
    {
        $this->courses->reorderUnits($unitIds);
    }

    /** @param list<int> $lessonIds */
    public function reorderLessons(array $lessonIds): void
    {
        $this->courses->reorderLessons($lessonIds);
    }

    /** @param array{unit_id: int, title: string, position: int, content_type: string, content: array|string|null} $attributes */
    public function createLesson(array $attributes): int
    {
        return $this->courses->createLesson($attributes);
    }

    /** @param array{title: string, position: int, content_type: string, content: array|string|null} $attributes */
    public function updateLesson(int $lessonId, int $expectedVersion, array $attributes): void
    {
        $this->courses->updateLesson($lessonId, $expectedVersion, $attributes);
    }

    public function archiveLesson(int $lessonId): void
    {
        $this->courses->archiveLesson($lessonId);
    }

    public function deleteLesson(int $lessonId): void
    {
        $this->courses->deleteLesson($lessonId);
    }
}
