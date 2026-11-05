<?php

namespace App\Modules\Students\Domain;

interface StudentRepository
{
    public function register(
        string $name,
        string $email,
        string $passwordHash,
        ?string $identifier,
        ?string $phone,
    ): Student;

    public function update(int $studentId, string $name, string $email, ?string $identifier, ?string $phone): void;

    public function setActive(int $studentId, bool $isActive): void;

    public function find(int $studentId): ?Student;

    /** @return list<Student> */
    public function all(?int $teacherUserId = null): array;

    /** @return list<array{id: int, name: string, email: string, identifier: ?string, phone: ?string, status: string, enrolled_at: string}> */
    public function forCourse(int $courseId): array;

    /** @return list<array{id: int, name: string, status: string, enrolled_at: string}> */
    public function coursesFor(int $studentId, ?int $teacherUserId = null): array;

    /**
     * @return list<array{
     *     id: int,
     *     enrollment_id: ?int,
     *     name: string,
     *     status: string,
     *     group_code: ?string,
     *     enrolled_at: string,
     *     progress: array{
     *         completed_lessons: int,
     *         total_lessons: int,
     *         percentage: int
     *     },
     *     assessments: list<array{
     *         id: int,
     *         title: string,
     *         total_score: int,
     *         passing_score: int,
     *         latest_attempt: ?array{
     *             id: int,
     *             attempt_number: int,
     *             score: int,
     *             passed: bool,
     *             status: string,
     *             submitted_at: ?string
     *         }
     *     }>
     * }>
     */
    public function coursesWithProgressAndAssessments(int $studentId, ?int $teacherUserId = null): array;

    public function canTeacherView(int $studentId, int $teacherUserId): bool;
}
