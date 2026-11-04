<?php

namespace App\Modules\Students\Infrastructure;

use App\Models\User;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Domain\Student as StudentRecord;
use App\Modules\Students\Domain\StudentRepository;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;

final class EloquentStudentRepository implements StudentRepository
{
    public function register(
        string $name,
        string $email,
        string $passwordHash,
        ?string $identifier,
        ?string $phone,
    ): StudentRecord {
        return DB::transaction(function () use ($name, $email, $passwordHash, $identifier, $phone): StudentRecord {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'must_change_password' => true,
                'is_active' => true,
            ]);
            $user->assignRole('estudiante');

            $student = Student::query()->create([
                'user_id' => $user->id,
                'identifier' => $identifier,
                'phone' => $phone,
                'is_active' => true,
            ]);

            return $this->toRecord($student->load('user'));
        });
    }

    public function update(int $studentId, string $name, string $email, ?string $identifier, ?string $phone): void
    {
        DB::transaction(function () use ($studentId, $name, $email, $identifier, $phone): void {
            $student = Student::query()->with('user')->findOrFail($studentId);
            $student->user->update(['name' => $name, 'email' => $email]);
            $student->update(['identifier' => $identifier, 'phone' => $phone]);
        });
    }

    public function setActive(int $studentId, bool $isActive): void
    {
        DB::transaction(function () use ($studentId, $isActive): void {
            $student = Student::query()->findOrFail($studentId);
            $student->user()->update(['is_active' => $isActive]);
            $student->update(['is_active' => $isActive]);
        });
    }

    public function find(int $studentId): ?StudentRecord
    {
        $student = Student::query()->with('user')->find($studentId);

        return $student ? $this->toRecord($student) : null;
    }

    public function all(?int $teacherUserId = null): array
    {
        return Student::query()
            ->with('user')
            ->when(
                $teacherUserId !== null,
                fn ($query) => $query->whereHas(
                    'enrollments.course.teachers',
                    fn ($teachers) => $teachers->whereKey($teacherUserId),
                ),
            )
            ->orderBy('id')
            ->get()
            ->map(fn (Student $student): StudentRecord => $this->toRecord($student))
            ->all();
    }

    public function forCourse(int $courseId): array
    {
        return Enrollment::query()
            ->with(['student.user', 'courseOffering:id,group_code'])
            ->where('course_id', $courseId)
            ->orderBy('enrolled_at')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->student->id,
                'enrollment_id' => $enrollment->id,
                'name' => $enrollment->student->user->name,
                'email' => $enrollment->student->user->email,
                'identifier' => $enrollment->student->identifier,
                'phone' => $enrollment->student->phone,
                'status' => $enrollment->status->value,
                'group_code' => $enrollment->courseOffering?->group_code,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
            ])
            ->all();
    }

    public function coursesFor(int $studentId, ?int $teacherUserId = null): array
    {
        return Enrollment::query()
            ->with(['course', 'courseOffering:id,group_code'])
            ->where('student_id', $studentId)
            ->when(
                $teacherUserId !== null,
                fn ($query) => $query->whereHas(
                    'course.teachers',
                    fn ($teachers) => $teachers->whereKey($teacherUserId),
                ),
            )
            ->orderBy('enrolled_at')
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->course->id,
                'enrollment_id' => $enrollment->id,
                'name' => $enrollment->course->name,
                'status' => $enrollment->status->value,
                'group_code' => $enrollment->courseOffering?->group_code,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
            ])
            ->all();
    }

    public function canTeacherView(int $studentId, int $teacherUserId): bool
    {
        return Student::query()
            ->whereKey($studentId)
            ->whereHas(
                'enrollments.course.teachers',
                fn ($teachers) => $teachers->whereKey($teacherUserId),
            )
            ->exists();
    }

    private function toRecord(Student $student): StudentRecord
    {
        return new StudentRecord(
            $student->id,
            $student->user_id,
            $student->user->name,
            $student->user->email,
            $student->identifier,
            $student->phone,
            $student->is_active && $student->user->is_active,
        );
    }
}
