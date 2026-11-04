<?php

namespace App\Modules\Courses\Domain;

interface CourseRepository
{
    /** @param array{name: string, description: array|null, objectives: array|null, intro_video_provider: string|null, intro_video_id: string|null} $attributes */
    public function createCourse(array $attributes): int;

    /** @param array{name: string, description: array|null, objectives: array|null, intro_video_provider: string|null, intro_video_id: string|null} $attributes */
    public function updateCourse(int $courseId, int $expectedVersion, array $attributes): void;

    public function coverPath(int $courseId): ?string;

    public function setCoverPath(int $courseId, ?string $path, ?string $mimeType): void;

    public function setCourseStatus(int $courseId, CourseStatus $status): void;

    public function isPublished(int $courseId): bool;

    public function deleteCourse(int $courseId, bool $confirmImpact): void;

    /** @param array{course_id: int, title: string, position: int} $attributes */
    public function createUnit(array $attributes): int;

    /** @param array{title: string, position: int} $attributes */
    public function updateUnit(int $unitId, array $attributes): void;

    public function deleteUnit(int $unitId): void;

    /** @param list<int> $unitIds */
    public function reorderUnits(array $unitIds): void;

    /** @param list<int> $lessonIds */
    public function reorderLessons(array $lessonIds): void;

    /** @param array{unit_id: int, title: string, position: int, content_type: string, content: ?string} $attributes */
    public function createLesson(array $attributes): int;

    /** @param array{title: string, position: int, content_type: string, content: array|string|null} $attributes */
    public function updateLesson(int $lessonId, int $expectedVersion, array $attributes): void;

    public function archiveLesson(int $lessonId): void;

    public function deleteLesson(int $lessonId): void;
}
