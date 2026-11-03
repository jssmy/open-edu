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

    public function canTeacherView(int $studentId, int $teacherUserId): bool;
}
