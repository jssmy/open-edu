<?php

namespace App\Modules\Progress\Infrastructure;

use App\Models\User;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Modules\Enrollments\Domain\EnrollmentStatus;
use App\Modules\Enrollments\Infrastructure\Models\Enrollment;
use App\Modules\Progress\Domain\ProgressRepository;
use App\Modules\Progress\Infrastructure\Models\Certificate;
use App\Modules\Students\Infrastructure\Models\Student;
use App\Shared\Exceptions\DomainException;
use App\Shared\RichText\RichTextDocumentValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class EloquentProgressRepository implements ProgressRepository
{
    public function __construct(
        private readonly ?CertificatePdfGenerator $pdfGenerator = null,
    ) {}

    public function coursesForStudent(int $studentId): array
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->whereHas('enrollments', fn (Builder $query) => $query
                ->where('student_id', $studentId)
                ->where('status', EnrollmentStatus::Active->value))
            ->with(['teachers' => fn ($query) => $query->select('users.id', 'users.name')])
            ->withCount('units')
            ->orderBy('name')
            ->get();

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
            'excerpt' => Str::limit(trim(RichTextDocumentValidator::plainText($course->description)), 180),
            'cover_url' => $course->coverUrl(),
            'units_count' => (int) ($course->units_count ?? 0),
            'status' => $course->status,
            'teachers' => $course->teachers->map(fn ($teacher): array => [
                'id' => $teacher->id,
                'name' => $teacher->name,
            ])->all(),
            'description' => is_string($course->description) ? $course->description : null,
            'completed_lessons' => (int) ($counts->get($course->id)->completed_lessons ?? 0),
            'total_lessons' => (int) ($counts->get($course->id)->total_lessons ?? 0),
        ])->all();
    }

    public function courseStudyData(int $courseId, ?int $studentId, ?int $userId = null, ?int $courseOfferingId = null): ?array
    {
        if ($studentId === null && $userId === null && $courseOfferingId === null) {
            return null;
        }

        $courseQuery = Course::query()
            ->whereKey($courseId);

        if ($courseOfferingId === null) {
            $courseQuery->where('status', 'published')
                ->whereHas('enrollments', function (Builder $query) use ($studentId, $userId): void {
                    $query->where('status', EnrollmentStatus::Active->value);
                    if ($studentId !== null) {
                        $query->where('student_id', $studentId);
                    } else {
                        $query->where('user_id', $userId);
                    }
                });
        }

        $course = $courseQuery
            ->with(['units.lessons' => fn (Relation $query) => $query
                ->where('is_archived', false)
                ->orderBy('position')])
            ->first();

        if ($course === null) {
            return null;
        }

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->when(
                $studentId !== null,
                fn (Builder $query) => $query->where('student_id', $studentId),
                fn (Builder $query) => $query->where('user_id', $userId),
            )
            ->with(['certificate', 'courseOffering'])
            ->first();

        $offeringId = $courseOfferingId ?? $enrollment?->course_offering_id;

        $lessons = $course->units->flatMap(fn (Unit $unit) => $unit->lessons);
        $completedLessonIds = $lessons->isEmpty()
            ? []
            : DB::table('lesson_progress')
                ->where(function ($query) use ($studentId, $userId, $offeringId): void {
                    if ($offeringId !== null) {
                        $query->where('course_offering_id', $offeringId);
                        if ($studentId !== null || $userId !== null) {
                            $query->orWhere(function ($sub) use ($studentId, $userId): void {
                                if ($studentId !== null) {
                                    $sub->where('student_id', $studentId);
                                } elseif ($userId !== null) {
                                    $sub->where('user_id', $userId);
                                }
                            });
                        }
                    } elseif ($studentId !== null) {
                        $query->where('student_id', $studentId);
                    } elseif ($userId !== null) {
                        $query->where('user_id', $userId);
                    }
                })
                ->whereIn('lesson_id', $lessons->pluck('id')->all())
                ->pluck('lesson_id')
                ->map(fn ($lessonId): int => (int) $lessonId)
                ->all();
        $completedLessons = array_fill_keys($completedLessonIds, true);

        $certificateData = null;
        if ($enrollment?->certificate !== null) {
            $cert = $enrollment->certificate;
            $certificateData = [
                'id' => $cert->id,
                'code' => $cert->code,
                'file_url' => $cert->file_url,
                'download_url' => "/certificates/{$cert->id}/download",
                'issued_at' => $cert->issued_at->toDateString(),
            ];
        }

        $allAssessmentsPassed = $this->hasPassedAllPublishedAssessments($course->id, $studentId, $userId);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'issues_certificate' => (bool) ($course->issues_certificate ?? true),
            'all_assessments_passed' => $allAssessmentsPassed,
            'certificate' => $certificateData,
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

    public function markLessonCompleted(
        ?int $studentId,
        int $lessonId,
        ?int $userId = null,
        ?int $courseOfferingId = null,
        string $action = 'complete',
    ): bool {
        $now = now();

        // 1. Avance de clase programada dictada por docente
        if ($courseOfferingId !== null) {
            $existing = DB::table('lesson_progress')
                ->where('course_offering_id', $courseOfferingId)
                ->where('lesson_id', $lessonId)
                ->first();

            if ($existing !== null) {
                if ($action === 'uncomplete' || $action === 'toggle') {
                    DB::table('lesson_progress')->where('id', $existing->id)->delete();

                    return false;
                }

                return true;
            }

            if ($action === 'uncomplete') {
                return false;
            }

            DB::table('lesson_progress')->insert([
                'course_offering_id' => $courseOfferingId,
                'user_id' => $userId,
                'student_id' => null,
                'lesson_id' => $lessonId,
                'completed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return true;
        }

        // 2. Avance individual de estudiante
        if ($studentId !== null) {
            $resolvedUserId = $userId ?? Student::query()->whereKey($studentId)->value('user_id');

            $existing = DB::table('lesson_progress')
                ->where('student_id', $studentId)
                ->where('lesson_id', $lessonId)
                ->first();

            if ($existing !== null) {
                if ($action === 'uncomplete' || $action === 'toggle') {
                    DB::table('lesson_progress')->where('id', $existing->id)->delete();

                    return false;
                }

                return true;
            }

            if ($action === 'uncomplete') {
                return false;
            }

            DB::table('lesson_progress')->insertOrIgnore([
                'student_id' => $studentId,
                'user_id' => $resolvedUserId,
                'course_offering_id' => null,
                'lesson_id' => $lessonId,
                'completed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return true;
        }

        // 3. Avance individual de usuario directo (docente autoinscrito / offline)
        if ($userId === null) {
            throw new RuntimeException('Se requiere un participante válido para registrar el progreso.');
        }

        $existing = DB::table('lesson_progress')
            ->where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->whereNull('course_offering_id')
            ->whereNull('student_id')
            ->first();

        if ($existing !== null) {
            if ($action === 'uncomplete' || $action === 'toggle') {
                DB::table('lesson_progress')->where('id', $existing->id)->delete();

                return false;
            }

            return true;
        }

        if ($action === 'uncomplete') {
            return false;
        }

        DB::table('lesson_progress')->insert([
            'student_id' => null,
            'user_id' => $userId,
            'course_offering_id' => null,
            'lesson_id' => $lessonId,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return true;
    }

    public function issueCertificate(int $courseId, int $userId, ?int $studentId = null): array
    {
        return DB::transaction(function () use ($courseId, $userId, $studentId): array {
            $course = Course::query()
                ->with(['category', 'units.lessons' => fn (Relation $query) => $query->where('is_archived', false)])
                ->findOrFail($courseId);

            if (! (bool) ($course->issues_certificate ?? true)) {
                throw new DomainException('Este curso no tiene habilitada la emisión de certificados.', 'certificate');
            }

            $enrollment = Enrollment::query()
                ->where('course_id', $course->id)
                ->where('status', EnrollmentStatus::Active->value)
                ->when(
                    $studentId !== null,
                    fn (Builder $query) => $query->where('student_id', $studentId),
                    fn (Builder $query) => $query->where('user_id', $userId),
                )
                ->lockForUpdate()
                ->first();

            if ($enrollment === null) {
                throw new DomainException('Debes contar con una matrícula activa en el curso para obtener el certificado.', 'certificate');
            }

            $existing = Certificate::query()
                ->where('enrollment_id', $enrollment->id)
                ->first();

            if ($existing !== null) {
                return [
                    'id' => $existing->id,
                    'code' => $existing->code,
                    'file_url' => $existing->file_url,
                    'download_url' => "/certificates/{$existing->id}/download",
                    'issued_at' => $existing->issued_at->toDateString(),
                ];
            }

            $lessons = $course->units->flatMap(fn (Unit $unit) => $unit->lessons);
            if ($lessons->isEmpty()) {
                throw new DomainException('El curso debe tener al menos una lección completada para emitir certificado.', 'certificate');
            }

            $completedCount = DB::table('lesson_progress')
                ->when(
                    $studentId !== null,
                    fn ($query) => $query->where('student_id', $studentId),
                    fn ($query) => $query->where('user_id', $userId),
                )
                ->whereIn('lesson_id', $lessons->pluck('id')->all())
                ->count();

            if ($completedCount < $lessons->count()) {
                throw new DomainException('Debes completar el 100% de las lecciones del curso para obtener el certificado.', 'certificate');
            }

            if (! $this->hasPassedAllPublishedAssessments($course->id, $studentId, $userId)) {
                throw new DomainException('Debes aprobar todas las evaluaciones publicadas del curso para obtener el certificado.', 'certificate');
            }

            $user = User::query()->findOrFail($userId);
            $code = sprintf('CERT-%s-%d-%d', now()->format('Ymd'), $course->id, $enrollment->id);
            $issuedAt = now();

            $generator = $this->pdfGenerator ?? new CertificatePdfGenerator;
            $fileUrl = $generator->generateAndStore([
                'code' => $code,
                'participant_name' => $user->name,
                'course_name' => $course->name,
                'category_name' => $course->category?->name,
                'issued_date' => $issuedAt->toDateString(),
            ]);

            $certificate = Certificate::query()->create([
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'user_id' => $user->id,
                'student_id' => $studentId,
                'code' => $code,
                'file_url' => $fileUrl,
                'issued_at' => $issuedAt,
            ]);

            return [
                'id' => $certificate->id,
                'code' => $certificate->code,
                'file_url' => $certificate->file_url,
                'download_url' => "/certificates/{$certificate->id}/download",
                'issued_at' => $certificate->issued_at->toDateString(),
            ];
        });
    }

    private function hasPassedAllPublishedAssessments(int $courseId, ?int $studentId, ?int $userId): bool
    {
        $publishedAssessmentIds = DB::table('assessments')
            ->where('course_id', $courseId)
            ->where('is_published', true)
            ->pluck('id')
            ->all();

        if ($publishedAssessmentIds === []) {
            return true;
        }

        $passedCount = DB::table('attempts')
            ->whereIn('assessment_id', $publishedAssessmentIds)
            ->where('status', 'submitted')
            ->where('passed', true)
            ->when(
                $studentId !== null,
                fn ($query) => $query->where('student_id', $studentId),
                fn ($query) => $query->where('user_id', $userId),
            )
            ->distinct()
            ->count('assessment_id');

        return $passedCount >= count($publishedAssessmentIds);
    }
}
