<?php

namespace App\Modules\Courses\Domain;

interface CourseRepository
{
    /** @param array{name: string, description: ?string, objectives: ?string} $attributes */
    public function createCourse(array $attributes): int;

    /** @param array{name: string, description: ?string, objectives: ?string} $attributes */
    public function updateCourse(int $courseId, array $attributes): void;

    public function setCourseStatus(int $courseId, CourseStatus $status): void;

    public function isPublished(int $courseId): bool;

    public function deleteCourse(int $courseId, bool $confirmImpact): void;

    /** @param list<int> $teacherIds */
    public function assignTeachers(int $courseId, array $teacherIds): void;

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

    /** @param array{title: string, position: int, content_type: string, content: ?string} $attributes */
    public function updateLesson(int $lessonId, array $attributes): void;

    public function archiveLesson(int $lessonId): void;

    public function deleteLesson(int $lessonId): void;
}
