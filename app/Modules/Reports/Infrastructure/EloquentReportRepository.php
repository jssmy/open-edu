<?php

namespace App\Modules\Reports\Infrastructure;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Reports\Application\ReportRepository;
use App\Modules\Reports\Application\ReportType;
use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final class EloquentReportRepository implements ReportRepository
{
    public function courseProgress(int $courseId): array
    {
        $course = Course::query()->findOrFail($courseId);

        return [
            'course' => ['id' => $course->id, 'name' => $course->name],
            'students' => $this->progress(['course_id' => $courseId], null),
        ];
    }

    public function myProgress(int $studentId): array
    {
        return $this->progress(['student_id' => $studentId], null);
    }

    public function enrollments(array $filters, ?int $teacherId): array
    {
        return $this->enrollmentQuery($filters, $teacherId)
            ->get()
            ->map(fn (stdClass $row): array => [
                'id' => (int) $row->id,
                'course_id' => (int) $row->course_id,
                'course_name' => $row->course_name,
                'student_id' => (int) $row->student_id,
                'student_name' => $row->student_name,
                'student_email' => $row->student_email,
                'status' => $row->status,
                'enrolled_at' => $row->enrolled_at,
            ])->all();
    }

    public function progress(array $filters, ?int $teacherId): array
    {
        $rows = $this->progressQuery($filters, $teacherId)
            ->get()
            ->map(fn (stdClass $row): array => [
                'id' => (int) $row->id,
                'course_id' => (int) $row->course_id,
                'course_name' => $row->course_name,
                'course_status' => $row->course_status,
                'course_deleted' => $row->course_deleted !== null,
                'student_id' => (int) $row->student_id,
                'student_name' => $row->student_name,
                'student_email' => $row->student_email,
                'enrollment_status' => $row->enrollment_status,
                'completed_lessons' => (int) $row->completed_lessons,
                'total_lessons' => (int) $row->total_lessons,
                'percentage' => (int) $row->percentage,
                'can_study' => $row->enrollment_status === 'active'
                    && $row->course_status === 'published'
                    && $row->course_deleted === null,
            ])->all();

        if ($rows === []) {
            return [];
        }

        $results = $this->results(
            array_intersect_key($filters, array_flip(['course_id', 'student_id'])),
            $teacherId,
        );
        $resultsByEnrollment = collect($results)->groupBy(
            fn (array $result): string => $result['course_id'].'-'.$result['student_id'],
        );

        return array_map(
            fn (array $row): array => [
                ...$row,
                'results' => $resultsByEnrollment
                    ->get($row['course_id'].'-'.$row['student_id'], collect())
                    ->values()
                    ->all(),
            ],
            $rows,
        );
    }

    public function results(array $filters, ?int $teacherId): array
    {
        return $this->resultsQuery($filters, $teacherId)
            ->get()
            ->map(fn (stdClass $row): array => [
                'course_id' => (int) $row->course_id,
                'course_name' => $row->course_name,
                'assessment_id' => (int) $row->assessment_id,
                'assessment_title' => $row->assessment_title,
                'student_id' => (int) $row->student_id,
                'student_name' => $row->student_name,
                'attempt_number' => (int) $row->attempt_number,
                'score' => (int) $row->score,
                'total_score' => (int) $row->total_score,
                'passed' => (bool) $row->passed,
                'submitted_at' => $row->submitted_at,
            ])->all();
    }

    public function exportRows(
        ReportType $report,
        array $filters,
        ?int $teacherId,
    ): iterable {
        $query = match ($report) {
            ReportType::Enrollments => $this->enrollmentQuery($filters, $teacherId),
            ReportType::Progress => $this->progressQuery($filters, $teacherId),
            ReportType::Results => $this->resultsQuery($filters, $teacherId),
        };
        $headings = array_keys($report->headings());

        return $this->cursorRows($query, $headings);
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     */
    private function enrollmentQuery(array $filters, ?int $teacherId): Builder
    {
        $query = DB::table('enrollments')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->select([
                'enrollments.id',
                'courses.id as course_id',
                'courses.name as course_name',
                'students.id as student_id',
                'users.name as student_name',
                'users.email as student_email',
                'enrollments.status',
                'enrollments.enrolled_at',
            ]);

        $this->applyEnrollmentFilters($query, $filters);
        $this->applyTeacherScope($query, $teacherId, 'courses.id');

        return $query->orderBy('courses.name')->orderBy('users.name')->orderBy('enrollments.id');
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     */
    private function progressQuery(array $filters, ?int $teacherId): Builder
    {
        $totalLessons = DB::table('units')
            ->join('lessons', 'lessons.unit_id', '=', 'units.id')
            ->where('lessons.is_archived', false)
            ->select('units.course_id')
            ->selectRaw('COUNT(lessons.id) AS total_lessons')
            ->groupBy('units.course_id');
        $completedLessons = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->where('lessons.is_archived', false)
            ->select('units.course_id', 'lesson_progress.student_id')
            ->selectRaw('COUNT(lesson_progress.lesson_id) AS completed_lessons')
            ->groupBy('units.course_id', 'lesson_progress.student_id');
        $query = DB::table('enrollments')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoinSub($totalLessons, 'course_lesson_counts', fn ($join) => $join
                ->on('course_lesson_counts.course_id', '=', 'courses.id'))
            ->leftJoinSub($completedLessons, 'student_lesson_counts', fn ($join) => $join
                ->on('student_lesson_counts.course_id', '=', 'courses.id')
                ->on('student_lesson_counts.student_id', '=', 'students.id'))
            ->select([
                'enrollments.id',
                'courses.id as course_id',
                'courses.name as course_name',
                'courses.status as course_status',
                'courses.deleted_at as course_deleted',
                'students.id as student_id',
                'users.name as student_name',
                'users.email as student_email',
                'enrollments.status as enrollment_status',
            ])
            ->selectRaw('COALESCE(student_lesson_counts.completed_lessons, 0) AS completed_lessons')
            ->selectRaw('COALESCE(course_lesson_counts.total_lessons, 0) AS total_lessons')
            ->selectRaw('CASE WHEN COALESCE(course_lesson_counts.total_lessons, 0) = 0 THEN 0 ELSE ROUND(COALESCE(student_lesson_counts.completed_lessons, 0) * 100.0 / course_lesson_counts.total_lessons) END AS percentage');

        $this->applyEnrollmentFilters($query, $filters);
        $this->applyTeacherScope($query, $teacherId, 'courses.id');

        return $query->orderBy('courses.name')->orderBy('users.name')->orderBy('enrollments.id');
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     */
    private function resultsQuery(array $filters, ?int $teacherId): Builder
    {
        $latestAttempts = DB::table('attempts')
            ->where('status', 'submitted')
            ->select('assessment_id', 'student_id')
            ->selectRaw('MAX(attempt_number) AS latest_attempt_number')
            ->groupBy('assessment_id', 'student_id');
        $query = DB::table('attempts')
            ->joinSub($latestAttempts, 'latest_attempts', fn ($join) => $join
                ->on('latest_attempts.assessment_id', '=', 'attempts.assessment_id')
                ->on('latest_attempts.student_id', '=', 'attempts.student_id')
                ->on('latest_attempts.latest_attempt_number', '=', 'attempts.attempt_number'))
            ->join('assessments', 'assessments.id', '=', 'attempts.assessment_id')
            ->join('courses', 'courses.id', '=', 'assessments.course_id')
            ->join('students', 'students.id', '=', 'attempts.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('attempts.status', 'submitted')
            ->select([
                'courses.id as course_id',
                'courses.name as course_name',
                'assessments.id as assessment_id',
                'assessments.title as assessment_title',
                'students.id as student_id',
                'users.name as student_name',
                'attempts.attempt_number',
                'attempts.score',
                'assessments.total_score',
                'attempts.passed',
                'attempts.submitted_at',
            ]);

        if (isset($filters['course_id'])) {
            $query->where('courses.id', $filters['course_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('students.id', $filters['student_id']);
        }

        if (array_key_exists('passed', $filters)) {
            $query->where('attempts.passed', $filters['passed']);
        }

        $this->applyTeacherScope($query, $teacherId, 'courses.id');

        return $query->orderBy('courses.name')
            ->orderBy('assessments.title')
            ->orderBy('users.name');
    }

    /**
     * @param  array<string, int|string|bool>  $filters
     */
    private function applyEnrollmentFilters(Builder $query, array $filters): void
    {
        if (isset($filters['course_id'])) {
            $query->where('courses.id', $filters['course_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('students.id', $filters['student_id']);
        }

        if (isset($filters['status'])) {
            $query->where('enrollments.status', $filters['status']);
        }
    }

    private function applyTeacherScope(Builder $query, ?int $teacherId, string $courseColumn): void
    {
        if ($teacherId === null) {
            return;
        }

        $query->whereExists(fn (Builder $teacherCourses) => $teacherCourses
            ->selectRaw('1')
            ->from('course_teacher')
            ->whereColumn('course_teacher.course_id', $courseColumn)
            ->where('course_teacher.user_id', $teacherId));
    }

    /**
     * @param  list<string>  $headings
     * @return Generator<int, array<string, string|int|bool|null>>
     */
    private function cursorRows(Builder $query, array $headings): Generator
    {
        foreach ($query->cursor() as $row) {
            $values = (array) $row;
            $csvRow = [];

            foreach ($headings as $heading) {
                $csvRow[$heading] = $values[$heading] ?? null;
            }

            yield $csvRow;
        }
    }
}
