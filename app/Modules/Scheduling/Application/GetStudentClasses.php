<?php

namespace App\Modules\Scheduling\Application;

use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Progress\Domain\CourseProgress;
use App\Modules\Scheduling\Infrastructure\Models\CourseOffering;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GetStudentClasses
{
    /**
     * @return array{
     *     classes: list<array<string, mixed>>,
     *     upcoming_classes?: list<array<string, mixed>>,
     * }
     */
    public function __invoke(int $studentId, ?string $today = null): array
    {
        $todayDate = $today ?? now()->toDateString();

        $enrollments = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('status', EnrollmentStatus::Active->value)
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->with([
                'courseOffering.teachers' => fn ($q) => $q->select('users.id', 'users.name'),
                'courseOffering.sessions.classroom',
                'course.units.lessons' => fn ($q) => $q->where('is_archived', false),
                'course.teachers' => fn ($q) => $q->select('users.id', 'users.name'),
            ])
            ->get();

        if ($enrollments->isEmpty()) {
            return [
                'classes' => [],
            ];
        }

        $courseIds = $enrollments->pluck('course_id')->unique()->values()->all();

        $counts = DB::table('units')
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

        $mappedClasses = $enrollments->map(function (Enrollment $enrollment) use ($counts, $todayDate): array {
            /** @var Course $course */
            $course = $enrollment->course;
            /** @var CourseOffering|null $offering */
            $offering = $enrollment->courseOffering;

            $completedLessons = (int) ($counts->get($course->id)->completed_lessons ?? 0);
            $totalLessons = (int) ($counts->get($course->id)->total_lessons ?? 0);
            $courseProgress = new CourseProgress($completedLessons, $totalLessons);

            $startsOn = $offering?->starts_on?->toDateString();
            $endsOn = $offering?->ends_on?->toDateString();

            // Status resolution
            $effectiveStatus = 'in_progress';
            $canAccess = true;
            $isFinished = false;

            if ($startsOn !== null && $startsOn > $todayDate) {
                $effectiveStatus = 'scheduled';
                $canAccess = false;
            } elseif ($endsOn !== null && $endsOn < $todayDate) {
                $effectiveStatus = 'finished';
                $isFinished = true;
            }

            $teachers = $offering && $offering->teachers->isNotEmpty()
                ? $offering->teachers->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->all()
                : $course->teachers->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->all();

            $excerpt = Str::limit(trim(RichTextDocumentValidator::plainText($course->description)), 180);

            return [
                'id' => $enrollment->id,
                'enrollment_id' => $enrollment->id,
                'offering_id' => $offering?->id,
                'course_id' => $course->id,
                'name' => $course->name,
                'excerpt' => $excerpt !== '' ? $excerpt : 'Sin descripción.',
                'cover_url' => $course->coverUrl(),
                'units_count' => $course->units->count(),
                'group_code' => $offering?->group_code ?? 'General',
                'modality' => $offering?->modality ?? 'Virtual',
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'teachers' => $teachers,
                'status' => $effectiveStatus,
                'can_access' => $canAccess,
                'is_finished' => $isFinished,
                'progress' => [
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'percentage' => $courseProgress->percentage(),
                ],
            ];
        });

        // Ordenar clases: primero in_progress (0), luego scheduled (1), luego finished (2)
        $statusWeight = [
            'in_progress' => 0,
            'scheduled' => 1,
            'finished' => 2,
        ];

        $sortedClasses = $mappedClasses->sort(function (array $a, array $b) use ($statusWeight) {
            $weightA = $statusWeight[$a['status']] ?? 3;
            $weightB = $statusWeight[$b['status']] ?? 3;

            if ($weightA !== $weightB) {
                return $weightA <=> $weightB;
            }

            // Si ambas son scheduled, la más próxima a iniciar primero
            if ($a['status'] === 'scheduled') {
                return ($a['starts_on'] ?? '') <=> ($b['starts_on'] ?? '');
            }

            // Si están en progreso, ordenar por nombre
            return strcmp($a['name'], $b['name']);
        })->values()->all();

        return [
            'classes' => $sortedClasses,
        ];
    }
}
