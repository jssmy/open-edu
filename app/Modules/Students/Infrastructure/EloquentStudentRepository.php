<?php

namespace App\Modules\Students\Infrastructure;

use App\Models\User;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Students\Domain\Student as StudentRecord;
use App\Modules\Students\Domain\StudentRepository;
use App\Modules\Students\Infrastructure\Models\Student;
use Illuminate\Database\Query\JoinClause;
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

    public function coursesWithProgressAndAssessments(int $studentId, ?int $teacherUserId = null): array
    {
        $enrollments = Enrollment::query()
            ->with([
                'course.assessments' => fn ($q) => $q->where('is_published', true)->orderBy('id'),
                'course.assessments.attempts' => fn ($q) => $q->where('student_id', $studentId)->orderByDesc('attempt_number'),
                'courseOffering:id,group_code',
            ])
            ->where('student_id', $studentId)
            ->when(
                $teacherUserId !== null,
                fn ($query) => $query->whereHas(
                    'course.teachers',
                    fn ($teachers) => $teachers->whereKey($teacherUserId),
                ),
            )
            ->orderBy('enrolled_at')
            ->get();

        if ($enrollments->isEmpty()) {
            return [];
        }

        $courseIds = $enrollments->pluck('course_id')->filter()->unique()->all();

        $progressCounts = DB::table('units')
            ->join('lessons', 'lessons.unit_id', '=', 'units.id')
            ->leftJoin('lesson_progress', function (JoinClause $join) use ($studentId): void {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.student_id', $studentId);
            })
            ->whereIn('units.course_id', $courseIds)
            ->where('lessons.is_archived', false)
            ->groupBy('units.course_id')
            ->select('units.course_id')
            ->selectRaw('COUNT(lessons.id) AS total_lessons')
            ->selectRaw('COUNT(lesson_progress.lesson_id) AS completed_lessons')
            ->get()
            ->keyBy('course_id');

        return $enrollments->map(function (Enrollment $enrollment) use ($progressCounts): array {
            $courseId = $enrollment->course_id;
            $counts = $progressCounts->get($courseId);
            $totalLessons = (int) ($counts->total_lessons ?? 0);
            $completedLessons = (int) ($counts->completed_lessons ?? 0);
            $percentage = $totalLessons > 0 ? (int) round(($completedLessons * 100) / $totalLessons) : 0;

            $assessments = $enrollment->course->assessments->map(function ($assessment): array {
                $latestAttempt = $assessment->attempts->first();

                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'total_score' => (int) $assessment->total_score,
                    'passing_score' => (int) $assessment->passing_score,
                    'latest_attempt' => $latestAttempt !== null ? [
                        'id' => $latestAttempt->id,
                        'attempt_number' => (int) $latestAttempt->attempt_number,
                        'score' => (int) $latestAttempt->score,
                        'passed' => (bool) $latestAttempt->passed,
                        'status' => $latestAttempt->status,
                        'submitted_at' => $latestAttempt->submitted_at?->toIso8601String(),
                    ] : null,
                ];
            })->values()->all();

            return [
                'id' => $enrollment->course->id,
                'enrollment_id' => $enrollment->id,
                'name' => $enrollment->course->name,
                'status' => $enrollment->status->value,
                'group_code' => $enrollment->courseOffering?->group_code,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                'progress' => [
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'percentage' => $percentage,
                ],
                'assessments' => $assessments,
            ];
        })->all();
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
