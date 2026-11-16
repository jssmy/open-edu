<?php

namespace App\Modules\Progress\Domain;

interface ProgressRepository
{
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
     *     completed_lessons: int,
     *     total_lessons: int
     * }>
     */
    public function coursesForStudent(int $studentId): array;

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     issues_certificate?: bool,
     *     all_assessments_passed?: bool,
     *     certificate?: array{
     *         id: int,
     *         code: string,
     *         file_url: string,
     *         download_url: string,
     *         issued_at: string
     *     }|null,
     *     units: list<array{
     *         id: int,
     *         title: string,
     *         position: int,
     *         lessons: list<array{
     *             id: int,
     *             title: string,
     *             position: int,
     *             content_type: string,
     *             content: ?string,
     *             is_completed: bool
     *         }>
     *     }>
     * }|null
     */
    public function courseStudyData(int $courseId, ?int $studentId, ?int $userId = null, ?int $courseOfferingId = null): ?array;

    public function markLessonCompleted(
        ?int $studentId,
        int $lessonId,
        ?int $userId = null,
        ?int $courseOfferingId = null,
        string $action = 'complete',
    ): bool;

    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     file_url: string,
     *     download_url: string,
     *     issued_at: string
     * }
     */
    public function issueCertificate(int $courseId, int $userId, ?int $studentId = null): array;
}
