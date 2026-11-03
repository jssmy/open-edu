<?php

namespace App\Modules\Progress\Infrastructure;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Progress\Domain\ProgressRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class EloquentProgressRepository implements ProgressRepository
{
    public function coursesForStudent(int $studentId): array
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->whereHas('enrollments', fn (Builder $query) => $query
                ->where('student_id', $studentId)
                ->where('status', EnrollmentStatus::Active->value))
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        if ($courses->isEmpty()) {
            return [];
        }

        $counts = DB::table('units')
            ->join('lessons', 'lessons.unit_id', '=', 'units.id')
            ->leftJoin('lesson_progress', function (JoinClause $join) use ($studentId): void {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.student_id', $studentId);
            })
            ->whereIn('units.course_id', $courses->modelKeys())
            ->where('lessons.is_archived', false)
            ->groupBy('units.course_id')
            ->select('units.course_id')
            ->selectRaw('COUNT(lessons.id) AS total_lessons')
            ->selectRaw('COUNT(lesson_progress.lesson_id) AS completed_lessons')
            ->get()
            ->keyBy('course_id');

        return $courses->map(fn (Course $course): array => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'completed_lessons' => (int) ($counts->get($course->id)->completed_lessons ?? 0),
            'total_lessons' => (int) ($counts->get($course->id)->total_lessons ?? 0),
        ])->all();
    }

    public function courseStudyData(int $courseId, int $studentId): ?array
    {
        $course = Course::query()
            ->whereKey($courseId)
            ->where('status', 'published')
            ->whereHas('enrollments', fn (Builder $query) => $query
                ->where('student_id', $studentId)
                ->where('status', EnrollmentStatus::Active->value))
            ->with(['units.lessons' => fn (Relation $query) => $query
                ->where('is_archived', false)
                ->orderBy('position')])
            ->first();

        if ($course === null) {
            return null;
        }

        $lessons = $course->units->flatMap(fn (Unit $unit) => $unit->lessons);
        $completedLessonIds = $lessons->isEmpty()
            ? []
            : DB::table('lesson_progress')
                ->where('student_id', $studentId)
                ->whereIn('lesson_id', $lessons->pluck('id')->all())
                ->pluck('lesson_id')
                ->map(fn ($lessonId): int => (int) $lessonId)
                ->all();
        $completedLessons = array_fill_keys($completedLessonIds, true);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'units' => $course->units->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'title' => $unit->title,
                'position' => $unit->position,
                'lessons' => $unit->lessons->map(fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'position' => $lesson->position,
                    'content_type' => $lesson->content_type,
                    'content' => $lesson->content,
                    'is_completed' => isset($completedLessons[$lesson->id]),
                ])->all(),
            ])->all(),
        ];
    }

    public function markLessonCompleted(int $studentId, int $lessonId): void
    {
        $now = now();
        DB::table('lesson_progress')->insertOrIgnore([
            'student_id' => $studentId,
            'lesson_id' => $lessonId,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (! DB::table('lesson_progress')
            ->where('student_id', $studentId)
            ->where('lesson_id', $lessonId)
            ->exists()) {
            throw new RuntimeException('No se pudo registrar el progreso de la lección.');
        }
    }
}
