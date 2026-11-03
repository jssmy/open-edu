<?php

namespace App\Modules\Courses\Infrastructure;

use App\Modules\Courses\Domain\ContentType;
use App\Modules\Courses\Domain\CourseRepository;
use App\Modules\Courses\Domain\CourseStatus;
use App\Modules\Courses\Domain\Lesson as LessonEntity;
use App\Modules\Courses\Infrastructure\Models\Course;
use App\Modules\Courses\Infrastructure\Models\Lesson;
use App\Modules\Courses\Infrastructure\Models\Unit;
use App\Shared\Exceptions\DomainException;
use App\Shared\Exceptions\StaleContentVersionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EloquentCourseRepository implements CourseRepository
{
    public function createCourse(array $attributes): int
    {
        return Course::query()->create($attributes)->id;
    }

    public function updateCourse(int $courseId, int $expectedVersion, array $attributes): void
    {
        $updated = Course::query()
            ->whereKey($courseId)
            ->where('lock_version', $expectedVersion)
            ->update([...$attributes, 'lock_version' => DB::raw('lock_version + 1')]);

        if ($updated !== 1) {
            throw new StaleContentVersionException(
                'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.',
            );
        }
    }

    public function setCourseStatus(int $courseId, CourseStatus $status): void
    {
        Course::query()->findOrFail($courseId)->update([
            'status' => $status->value,
            'lock_version' => DB::raw('lock_version + 1'),
        ]);
    }

    public function isPublished(int $courseId): bool
    {
        return Course::query()->whereKey($courseId)->where('status', CourseStatus::Published->value)->exists();
    }

    public function deleteCourse(int $courseId, bool $confirmImpact): void
    {
        if (Schema::hasTable('enrollments')
            && DB::table('enrollments')->where('course_id', $courseId)->exists()
            && ! $confirmImpact) {
            throw new DomainException(
                'Este curso tiene inscripciones. Confirma la eliminación para conservar su historial.',
                'confirm_impact',
            );
        }

        Course::query()->findOrFail($courseId)->delete();
    }

    public function assignTeachers(int $courseId, array $teacherIds): void
    {
        Course::query()->findOrFail($courseId)->teachers()->sync($teacherIds);
    }

    public function createUnit(array $attributes): int
    {
        return Unit::query()->create($attributes)->id;
    }

    public function updateUnit(int $unitId, array $attributes): void
    {
        Unit::query()->findOrFail($unitId)->update($attributes);
    }

    public function deleteUnit(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);
        $lessonIds = $unit->lessons()->pluck('id');

        if (Schema::hasTable('lesson_progress')
            && DB::table('lesson_progress')->whereIn('lesson_id', $lessonIds)->exists()) {
            throw new DomainException('Las unidades con lecciones que tienen progreso no se pueden eliminar.');
        }

        $unit->delete();
    }

    public function reorderUnits(array $unitIds): void
    {
        $units = Unit::query()->whereIn('id', $unitIds)->get();

        if ($unitIds === []
            || $units->count() !== count(array_unique($unitIds))
            || $units->pluck('course_id')->unique()->count() !== 1
            || Unit::query()->where('course_id', $units->first()->course_id)->count() !== count($unitIds)) {
            throw new DomainException('Las unidades deben pertenecer al mismo curso.');
        }

        DB::transaction(function () use ($unitIds): void {
            foreach ($unitIds as $index => $unitId) {
                Unit::query()->whereKey($unitId)->update(['position' => $index + 1]);
            }
        });
    }

    public function reorderLessons(array $lessonIds): void
    {
        $lessons = Lesson::query()->whereIn('id', $lessonIds)->get();

        if ($lessonIds === []
            || $lessons->count() !== count(array_unique($lessonIds))
            || $lessons->pluck('unit_id')->unique()->count() !== 1
            || Lesson::query()->where('unit_id', $lessons->first()->unit_id)->count() !== count($lessonIds)) {
            throw new DomainException('Las lecciones deben pertenecer a la misma unidad.');
        }

        DB::transaction(function () use ($lessonIds): void {
            foreach ($lessonIds as $index => $lessonId) {
                Lesson::query()->whereKey($lessonId)->update([
                    'position' => $index + 1,
                    'lock_version' => DB::raw('lock_version + 1'),
                ]);
            }
        });
    }

    public function createLesson(array $attributes): int
    {
        return Lesson::query()->create($attributes)->id;
    }

    public function updateLesson(int $lessonId, int $expectedVersion, array $attributes): void
    {
        $updated = Lesson::query()
            ->whereKey($lessonId)
            ->where('lock_version', $expectedVersion)
            ->update([...$attributes, 'lock_version' => DB::raw('lock_version + 1')]);

        if ($updated !== 1) {
            throw new StaleContentVersionException(
                'El contenido cambió desde que abriste esta página. Recarga para revisar los cambios.',
            );
        }
    }

    public function archiveLesson(int $lessonId): void
    {
        Lesson::query()->findOrFail($lessonId)->update([
            'is_archived' => true,
            'lock_version' => DB::raw('lock_version + 1'),
        ]);
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $hasProgress = Schema::hasTable('lesson_progress')
            && DB::table('lesson_progress')->where('lesson_id', $lessonId)->exists();

        (new LessonEntity(
            $lesson->id,
            $lesson->unit_id,
            $lesson->title,
            $lesson->position,
            ContentType::from($lesson->content_type),
            $lesson->content,
            $lesson->is_archived,
        ))->assertCanBeDeleted($hasProgress);

        $lesson->delete();
    }
}
